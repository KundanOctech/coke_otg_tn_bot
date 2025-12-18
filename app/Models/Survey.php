<?php

namespace App\Models;

class Survey extends BaseModel
{
    public static $cdpOptions = [
        'q1' => [
            'answer1_a' => 'Consume_Weekly',
            'answer1_b' => 'Consume_Monthly',
            'answer1_c' => 'Consume_Occasionally',
            'answer1_d' => 'Consume_Never'
        ],
        'q2' => [
            'answer2_a' => 'Consumption_first_choice',
            'answer2_b' => 'Consumption_seriously_consider',
            'answer2_c' => 'Consumption_might_consider',
            'answer2_d' => 'Consumption_not_consider'
        ],
        'q3' => [
            'answer3_a' => 'BrandPerception_3',
            'answer3_b' => 'BrandPerception_2',
            'answer3_c' => 'BrandPerception_1',
            'answer3_d' => 'BrandPerception_0',
            'answer3_e' => 'BrandPerception_-1',
            'answer3_f' => 'BrandPerception_-2',
            'answer3_g' => 'BrandPerception_-3'
        ]
    ];

    public static $questionOptions = [
        'q1' => [
            'answer1_a' => 'Consume weekly',
            'answer1_b' => 'Consume monthly',
            'answer1_c' => 'Consume occasionally',
            'answer1_d' => 'Never consumes'
        ],
        'q2' => [
            'answer2_a' => 'First choice',
            'answer2_b' => 'Seriously consider',
            'answer2_c' => 'Might consider',
            'answer2_d' => 'Not consider'
        ],
        'q3' => [
            'answer3_a' => '+3',
            'answer3_b' => '+2',
            'answer3_c' => '+1',
            'answer3_d' => '0',
            'answer3_e' => '-1',
            'answer3_f' => '-2',
            'answer3_g' => '-3'
        ]
    ];


    public static function validateQuestionNumber($questionKey)
    {
        return !empty($questionKey) && array_key_exists($questionKey, self::$questionOptions);
    }

    public static function validateOption($questionKey, $answer)
    {
        return array_key_exists($answer, self::$questionOptions[$questionKey]);
    }
}

/**
 * Survey
 * --------------------------------
 * id
 * user_id
 * source
 * source_id
 * brand
 * mobile
 * answer_1
 * answer_2
 * answer_3
 * created_date
 * created_at
 * updated_at
 */
