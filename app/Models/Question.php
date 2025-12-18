<?php

namespace App\Models;

class Question extends BaseModel
{

    public static function getQuestion($uniqueCode)
    {
        $questionData = Question::where('is_active', 1)
            ->inRandomOrder()
            ->first();

        UniqueCode::where('code', $uniqueCode)->update([
            'question_id' => $questionData->id
        ]);

        $language = User::getLanguage($questionData->language);
        $questionKey = 'question_' . $language;
        $optionAKey = 'option_a_' . $language;
        $optionBKey = 'option_b_' . $language;
        $optionCKey = 'option_c_' . $language;
        $optionDKey = 'option_d_' . $language;

        return [
            'question' => $questionData->$questionKey,
            'option_a' => $questionData->$optionAKey,
            'option_b' => $questionData->$optionBKey,
            'option_c' => $questionData->$optionCKey,
            'option_d' => $questionData->$optionDKey,
        ];
    }
}
/**
 * ------------------------------------------------------------------------
 * Question
 * ------------------------------------------------------------------------
 * id
 * question_en
 * question_ta
 * option_a_en
 * option_a_ta
 * option_b_en
 * option_b_ta
 * option_c_en
 * option_c_ta
 * option_d_en
 * option_d_ta
 * correct_option
 * is_active
 * created_at
 * updated_at
 */
