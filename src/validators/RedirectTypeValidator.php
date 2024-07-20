<?php
namespace xorb\search\validators;

use yii\validators\Validator;

class RedirectTypeValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;

        if (!in_array($value, [
            '301',
            '302',
            '410',
        ])) {
            $model->$attribute = '301';
        }
    }
}
