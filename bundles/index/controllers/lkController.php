<?php
/**
 * Created by PhpStorm.
 * User: haron
 * Date: 14.07.2020
 * Time: 17:10
 */

class lkController extends baseController
{
    public function index()
    {
        $this->request->setViewVariable('page', 'Личный кабинет');
        $this->request->setViewVariable('header', '');


        $actionTitles = ['order' => 'Заказано', 'boost' => 'Забущено', 'revive' => 'Воскрешено'];
        $thresholdDate = 1570838400; // 12.10.19 - дата ввода новой системы счёта поинтов
        $reviveThresholdDate = 1559520000; // 03.06.19 - дата увеличения стоимости ревайва

        $user = $this->dbConn->query("SELECT * FROM users WHERE twitch_id = {$_SESSION['id']}")->fetch_assoc();
        if (@$_GET['mode'] === 'dev') {
            $user = $this->dbConn->query("SELECT * FROM users WHERE twitch_id = {$_GET['user']}")->fetch_assoc();
        }

        $checkDays = [];
        $d = $this->dbConn->query("SELECT DATE_FORMAT(check_date, '%Y-%m-%d') AS check_day FROM subscriptions WHERE user_twitch_id = 40955336 ORDER BY check_date ASC");
        while ($checkDate = $d->fetch_assoc()) {
            $checkDays[$checkDate['check_day']] = 0;
        }

        $d = $this->dbConn->query("SELECT id, DATE_FORMAT(check_date, '%Y-%m-%d') AS check_day, overall_sub_days FROM subscriptions WHERE user_twitch_id = {$user['twitch_id']} ORDER BY check_date ASC");
        while ($subDate = $d->fetch_assoc()) {
            $checkDays[$subDate['check_day']] = $subDate['overall_sub_days'];
        }

        ksort($checkDays);

        $pointsGained = 0;
        $cumulativeSubDays = 0;
        $tableRows = $prehistoricTableRows = [];
        $isFirstSubAfterThreshold = false;
        $prevRow = [
            'timestamp' => 0,
            'date' => '',
            'sub_overall' => '-',
            'sub_streak' => '-',
            'event' => '',
            'isPositive' => false,
            'action' => ''
        ];

// Расчёт затрат поинтов
        $a = $this->dbConn->query("SELECT a.*, g.title FROM actions AS a JOIN games AS g ON g.id = a.game WHERE a.author_id = {$user['twitch_id']} ORDER BY a.date ASC");
        $actualUsedPoints = 0;
        $overallUsedPoints = 0;
        while ($action = $a->fetch_assoc()) {
            $row = [
                'timestamp' => strtotime($action['date']),
                'date' => $action['date'],
                'sub_overall' => '',
                'sub_streak' => '',
                'event' => '',
                'isPositive' => false,
                'action' => "{$actionTitles[$action['action_type']]}: {$action['title']}"
            ];

            $pointsUsage = 1;
            if ($action['action_type'] == 'revive') {
                $pointsUsage++;
                if (strtotime($action['date']) > $reviveThresholdDate) {
                    $pointsUsage++;
                }
            }
            $row['event'] = "-$pointsUsage поинт";

            $overallUsedPoints += $pointsUsage;
            if (strtotime($action['date']) >= $thresholdDate) {
//        echo("{$sub['username']} {$action['date']} -1 point <br>");
                $actualUsedPoints += $pointsUsage;
                $tableRows[] = $row;
            } else {
                $prehistoricTableRows[] = $row;
            }
        }

        if ($user['prehistoric_sub_points'] !== '0' ||
            $this->dbConn->query("SELECT id FROM subscriptions WHERE user_twitch_id = {$user['twitch_id']} AND check_date < '" . array_keys($checkDays)[0] . "'")->fetch_array() !== null ||
            $this->dbConn->query("SELECT id FROM actions WHERE author_id = {$user['twitch_id']} AND date < '" . array_keys($checkDays)[0] . "'")->fetch_array() !== null
        ) {
//            echo "{$sub['username']} subbed before " . array_keys($checkDays)[0] . "<br>";
            $isFirstSub = false;
        } else {
            $isFirstSub = true;
        }

        foreach ($checkDays as $checkDay => $cumulativeSubDays) {
            $checkTimestamp = strtotime($checkDay);
            $gap = $prevRow['timestamp'] === 0 ? 0 : ($checkTimestamp - $prevRow['timestamp']) / 86400;
            $row = [
                'timestamp' => $checkTimestamp,
                'date' => $checkDay,
                'sub_overall' => '-',
                'sub_streak' => '-',
                'event' => '',
                'isPositive' => false,
                'action' => ''
            ];
            if ($cumulativeSubDays) {
//                $cumulativeSubDays = $cumulativeSubDays + ($prevRow['isPositive'] ? $gap : 1);
                $row['sub_overall'] = $cumulativeSubDays;
                $row['sub_streak'] = $prevRow['isPositive'] ? ($prevRow['sub_streak'] + $gap) : 1;
                $row['isPositive'] = true;
            } else {
//        echo("{$sub['username']}_no_sub_true $checkDay <br>");
                $row['event'] .= $prevRow['isPositive'] ? ' Стрик прерван' : '';
            }

            if ($cumulativeSubDays && $isFirstSub) {
                $row['event'] = '+3 поинт';
                $row['action'] = 'Первая подписка';
                $isFirstSub = false;

                if ($checkTimestamp > $thresholdDate) {
                    $pointsGained += 3;
                    $isFirstSubAfterThreshold = true;
                }
            }

            if ($isFirstSubAfterThreshold) $adaptedPoints = $pointsGained - 2;
            else $adaptedPoints = $pointsGained + 1;

            if ($prevRow['isPositive'] && $cumulativeSubDays >= $adaptedPoints * 30 && $overallUsedPoints >= 3) {
//        echo("{$sub['username']}_sub_true $checkDay +1 point <br>");
                $row['event'] = '+1 поинт';
                $pointsGained++;
            }

            if ($checkTimestamp > $thresholdDate) {
                $tableRows[] = $row;
            } else {
                $prehistoricTableRows[] = $row;
            }
            $prevRow = $row;
        }

        usort($prehistoricTableRows, function ($a, $b) {
            return $a['date'] < $b['date'] ? -1 : 1;
        });
        usort($tableRows, function ($a, $b) {
            return $a['date'] < $b['date'] ? -1 : 1;
        });

        $table = '<details>
    <summary>Информация</summary>
    <table>
        <tr>
            <th rowspan="2">Дата проверки</th>
            <th colspan="2">Подписка</th>
            <th rowspan="2">Событие</th>
            <th rowspan="2">Действие</th>
        </tr>
        <tr>
            <th>Стрик, дней</th>
            <th>Всего, дней</th>
        </tr>
        <tr>
            <th colspan="5" style="color:red">Доисторическая информация</th>
        </tr>
';
        foreach ($prehistoricTableRows as $tableRow) {
            $table .= "<tr>
        <td>{$tableRow['date']}</td>
        <td>{$tableRow['sub_streak']}</td>
        <td>{$tableRow['sub_overall']}</td>
        <td>{$tableRow['event']}</td>
        <td>{$tableRow['action']}</td>
    </tr>";
        }
        $table .= '<tr><th colspan="5" style="color:red">Актуальная информация</th></tr>';
        foreach ($tableRows as $tableRow) {
            $table .= "<tr>
        <td>{$tableRow['date']}</td>
        <td>{$tableRow['sub_streak']}</td>
        <td>{$tableRow['sub_overall']}</td>
        <td>{$tableRow['event']}</td>
        <td>{$tableRow['action']}</td>
    </tr>";
        }
        $table .= '</table></details>';

        $calculatedPoints = $user['prehistoric_sub_points'] + $pointsGained - $actualUsedPoints;
        $body = "
<h1>Личный кабинет {$user['username']}</h1>
<table>
<tr><th>Twitch ID: </th><td>{$user['twitch_id']}</td></tr>
<tr><th>Дата регистрации:</th><td>{$user['reg_date']}</td></tr>
<tr><th colspan='2'>Поинты за подписку</th></tr>
<tr><th>Расчётное количество:</th><td>{$user['prehistoric_sub_points']} изначально + $pointsGained - $actualUsedPoints = $calculatedPoints</td></tr>
<tr><th>Фактическое количество:</th><td>{$user['sub_points']} <span style='cursor: pointer; color: blue;' title='Расчётное количество - то, что посчитано по этой истории, а фактическое - то, что указано в базе данных. Если числа отличаются - сообщите Харону, он глянет глазами.'>[?]</span></td></tr>
</table>
$table
";

        $this->request->setViewVariable('body', $body);
    }
}