yii2 pdf generator
==================
A yii2 wrapper for wkhtmltopdf


Requirements
------------
- [wkhtmltopdf](https://github.com/wkhtmltopdf/wkhtmltopdf)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require --prefer-dist caijq4ever/yii2-pdf "dev-master"
```

or add

```
"caijq4ever/yii2-pdf": "dev-master"
```

to the require section of your `composer.json` file.


Usage
-----

1.Edit `config/web.php` file
```php
'components' => [
    ...
    'pdf' => [
        'class' => 'junqi\pdf\Pdf',
        'tmpDir' => '@runtime/pdf/',
        'options' => [
            'headerLeft' => 'world,hello',
            'headerLine' => true,
            //more options see `wkhtmltopdf -H`
        ],
    ],
    ...
],
```

2.Add following code somewhere in your yii2 project

2.1.Just get file
```php
    $html = <<<HTML
<h1>hello,world</h1>
HTML;

try {
    $fileName = Yii::$app->pdf
        ->loadHtml($html)
        ->execute()
        ->getFile('test.pdf');
    echo $fileName;
} catch (\junqi\pdf\PdfException $e) {
    echo $e->getMessage();
}
```

2.2.Download file with browser
```php
    $html = <<<HTML
<h1>hello,world</h1>
HTML;

try {
    Yii::$app->pdf
        ->loadHtml($html)
        ->execute()
        ->sendFile('test.pdf');
} catch (\junqi\pdf\PdfException $e) {
    echo $e->getMessage();
}
```