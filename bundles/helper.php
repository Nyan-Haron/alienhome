<?php

class helper
{
    /**
     * @param int $playerId
     * @param Db $db
     * @return array
     */
    public static function calculatePoints($playerId, $db) {
        if (!$playerId)
            return [
                'points' => 0,
                'overallPoints' => 0,
                'nextPointDate' => '',
                'lastActionDate' => '',
                'usedPoints' => 0,
                'log' => [],
            ];
        $actions = [];
        $usedPoints = 0;
        $lastActionDate = '';
        $nextPointDate = '';
        $playerInfo = $db->query("SELECT * FROM users WHERE twitch_id = $playerId")->fetch_assoc();
        $log = [
            strtotime($playerInfo['reg_date']) => [
                'event' => 'Registration',
                'date' => $playerInfo['reg_date'],
                'points' => '+3',
                'mark' => false,
            ]
        ];
        $a = $db->query("SELECT * FROM actions WHERE author_id = $playerId ORDER BY date ASC");
        while ($action = $a->fetch_assoc()) {
            $actions[] = $action;
            $usedPoints++;
            $pointsUsage = 1;
            if ($action['action_type'] == 'revive') {
                $usedPoints++;
                $pointsUsage++;
                if (strtotime($action['date']) > 1559520000) {
                    $usedPoints++;
                    $pointsUsage++;
                }
            }

            $log[strtotime($action['date'])] = [
                'event' => $action['action_type'],
                'date' => $action['date'],
                'points' => '-'.$pointsUsage,
                'mark' => false,
            ];

            if ($usedPoints == 3) {
                $lastActionDate = $action['date'];
                $log[strtotime($action['date'])]['mark'] = true;
            }
        }

        if ($usedPoints >= 3) {
            $d = $db->query("SELECT * FROM subscriptions WHERE user_twitch_id = $playerId AND granted_point = FALSE ORDER BY check_date ASC LIMIT 1");
            $checkDate = $d->fetch_assoc()['check_date'];
            $nextPointDate = $checkDate ? date("Y-m-d H:i:s", strtotime($checkDate) + 2592000) : '';
        }

        ksort($log);

        return [
            'points' => $playerInfo['sub_points'],
            'lastActionDate' => $lastActionDate,
            'nextPointDate' => $nextPointDate,
            'usedPoints' => $usedPoints,
            'log' => $log,
        ];
    }
}