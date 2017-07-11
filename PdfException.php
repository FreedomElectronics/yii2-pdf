<?php

namespace junqi\pdf;

use yii\base\Exception;

class PdfException extends Exception
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'PdfException';
    }
}