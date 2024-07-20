<?php
namespace xorb\search\validators;

use yii\validators\Validator;

class HitScorePeriodValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;

        if (!in_array($value, [
            'day',
            'week',
            'month',
            'three-months',
            'six-months',
            'year',
            'all',
        ])) {
            $model->$attribute = 'year';
        }
    }
}
