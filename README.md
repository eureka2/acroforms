This library is a complete rewrite for php> = 7.1 of [script93](http://www.fpdf.org/en/script/script93.php) developed by Olivier, with additional features:
* Supports linearized PDF forms
* Supports multi-pages PDF forms
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
$pdf->load($fields)->merge();
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
$pdf->merge();
if (php_sapi_name() == 'cli') {
    $pdf->output('F', 'filled.pdf');
} else {
    $pdf->output('I', 'filled.pdf');
    // or $pdf->output('D', 'filled.pdf');
}
?>
```
# Methods

|Name           |Arguments                            |Description                       |
|---------------|-------------------------------------|----------------------------------|
|allow          |1. permissions : array (optional)    |Allow permissions                 |
|compress       |                                     |use the compress filter to restore compression|
|encrypt        |1. bits : int (0, 40,or 128)         |define encrytion to the given bits|
|fix            |                                     |try to fix a corrupted PDF file   |
|flatten        |                                     |flatten output to remove form from pdf file keeping field datas |
|getButtonFields|                                     |Retrieve fields that are buttons  |
|getField       |1. fieldName                         |Retrieve a form field by its name |
|getTextFields  |                                     |Retrieve fields that are text type|
|info           |                                     |Retrieve information from the pdf |
|load           |1. data : array or string            |load a form data to be merged     |
|merge          |1. flatten : bool (optional)         |Merge the data with the PDF file  |
|password       |1. type : string ('owner' or 'user')<br>2. code : string (password code)|define a password code|
|setMode        |1. mode : string<br>2. value : bool  |set a mode                        |
|setSupport     |1. support : string (native or pdftk)|change the support                |
|uncompress     |                                     |apply the uncompress filter       |
|output         |1. destination : char (I, D, F or S)<br>2. name : string (PDF file name)|output PDF to some destination    |

# Copyright and license

&copy; 2019 Eureka2 - Jacques Archimède. Code released under the [MIT license](https://github.com/eureka2/acroforms/blob/master/LICENSE).