<?php
namespace xorb\search\validators;

use yii\validators\Validator;

class SitemapChangefreqValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;

        if (!in_array($value, [
            'always',
            'hourly',
            'daily',
            'weekly',
            'monthly',
            'yearly',
            'never',
        ])) {
            $model->$attribute = 'weekly';
        }
    }
}
