# Form filling

This script allows to merge data into a PDF form. Given a template PDF with text fields, it's
possible to inject values in two different ways:
- from a PHP array
- from an <abbr title="Forms Data Format">FDF</abbr> file

The resulting document is produced by the Output() method, which works the same as for FPDF.

Note: if your template PDF is not compatible with this script, you can process it with
[PDFtk](https://www.pdflabs.com/tools/pdftk-server/) this way:

`pdftk modele.pdf output modele2.pdf`
  
Then try again with modele2.pdf.

# Usage

## Standalone Script
Load the class file by calling

`require_once '/abolute/path/to/fpdm.php';`

or

`require_once './relative/path/to/fpdm.php';`

```php
<?php

/***************************
  Sample using a PHP array
****************************/

$fields = array(
    'name'    => 'My name',
    'address' => 'My address',
    'city'    => 'My city',
    'phone'   => 'My phone number'
);

$pdf = new FPDM('template.pdf');
$pdf->Load($fields, false); // second parameter: false if field values are in ISO-8859-1, true if UTF-8
$pdf->Merge();
$pdf->Output();
?>
```
