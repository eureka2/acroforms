This library is a complete rewrite for php> = 7.1 of [script93](http://www.fpdf.org/en/script/script93.php) developed by Olivier, with additional features:
* Supports linearized PDFs
* Supports PDF 'radio', 'check box' and 'choice' fields in addition to 'text' fields
* UTF-8 support

With this library, you can merge (inject) data into a PDF form (AcroForm), either from a PHP array, or from a FDF file.

# Dependency

* [PDFtk Server](https://www.pdflabs.com/tools/pdftk-server/)

# Installation

`composer require eureka2/acroforms`

# Usage

* With a PHP array:

```php
<?php

require_once 'path/to/src/autoload.php';

use acroforms\AcroForm;

$fields = [
    'text' => [ // text fields 
        'text_field1'    => 'value of text_field1',
        'text_field2'    => 'value of text_field2',
        ......
    ],
    'button' => [ // button fields
        'radio1' => 'value of radio1',
        'checkbox1' => 'value of checkbox1',
        ........
    ]
];

$pdf = new AcroForm('exemple.pdf', [
    'pdftk' => '/absolute/path/to/pdftk'
]);
$pdf->load($fields);
$pdf->merge();
if (php_sapi_name() == 'cli') {
	file_put_contents("filled.pdf", $pdf->output('S'));
} else {
	$pdf->output('I', 'filled.pdf');
}
?>
```

* With a pre-filled FDF file:

```php
<?php

require_once 'path/to/src/autoload.php';

use acroforms\AcroForm;

$pdf = new AcroForm('example.pdf', [
    'fdf' => 'example.fdf',
    'pdftk' => '/absolute/path/to/pdftk'
]);
$pdf->load($fields);
$pdf->merge();
if (php_sapi_name() == 'cli') {
    $pdf->output('F', 'filled.pdf');
} else {
    $pdf->output('I', 'filled.pdf');
    // or $pdf->output('D', 'filled.pdf');
}
?>
```

# Copyright and license

&copy; 2019 Eureka2 - Jacques Archimède. Code released under the [MIT license](https://github.com/eureka2/acroforms/blob/master/LICENSE).