<?php 
/**
* Clase pare imprimir tickets.
*/
trait TicketWriter
{   
    public function addText($text = '', $linebreake = TRUE, $center = FALSE)
    {
        $text = substr($text, 0, $this->anchoPapel);
        if ($text != '') {
            if ($center) {
                $this->ticket .= $this->textCenter($text);
            } else {
                $this->ticket .= $text;
            }            
        } 
        if ($linebreake) {
            $this->ticket .= "\n";
        }                
    }

    public function addTextBold($text = '', $brake = TRUE, $center = FALSE)
    {
        $text = substr($text, 0, $this->anchoPapel);
        $text = chr(27) . chr(69) . chr(49) . $text . chr(27) . chr(69) . chr(48);
        if ($text != '') {
            if ($center) {
                $this->ticket .= $this->textCenter($text);
            } else {
                $this->ticket .= $text;
            }            
        } 
        if ($brake) {
            $this->addLineBreak();
        }           
    }

    public function addBigText($text = '', $linebreake = TRUE, $center = FALSE)
    {
        if ($text != '') {
            if ($center) {
                $this->ticket .= $this->textCenter($text);
            } else {
                $this->ticket .= $text;
            }            
        } 
        if ($linebreake) {
            $this->ticket .= "\n";
        }                
    }

    public function addMultiLineText($text)
    {
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            $this->addBigText($line);
        }
    }

    public function addLineBreak($n = 1)
    {
        for ($i=0; $i < $n; $i++) { 
            $this->ticket .= "\n";
        }        
    }

    public function addSplitter($splitter = '-')
    {
        $line = '';
        for ($i = 0; $i < $this->anchoPapel; $i++) {
            $line .= $splitter;
        }

        $this->ticket .= $line . "\n";
    }

    public function addTextMultiLine($text)
    {
        if ($this->sinComandos) {
            $this->ticket .= $text;
        } else {
            $this->ticket .= chr(27) . chr(33) . chr(56) . $text . chr(27) . chr(33) . chr(1);
        }
    }

    public function addLabelValue($label, $value, $align = '')
    {
        $texto = $label;
        $ancho = $this->anchoPapel - strlen($label);

        $value = substr($value, 0, $ancho);
        $texto .= sprintf('%' . $align . $ancho . 's', $value);

        $this->ticket .= $texto;
        $this->addLineBreak();
    }

    public function addBarcode($text = '')
    {
        $barcode = '';
        $barcode .= chr(27) . chr(97) . chr(49); #justification n=0,48 left n=2,49 center n=3,50 right
        $barcode .= chr(29) . chr(104) . chr(70); #barcode height in dots n=100, 1 < n < 255
        $barcode .= chr(29) . chr(119) . chr(2); #barcode width multiplier n=1, n=2, 
        $barcode .= chr(29) . chr(72) . chr(50); #barcode HRI position n=1,49 above n=2,50 below 
        $barcode .= chr(29) . chr(107) . chr(4) . $text . chr(0);
        $this->ticket .= $barcode;
    }

    public function textCenter($word = '', $ancho = FALSE)
    {
        if (!$ancho) {
            $ancho = $this->anchoPapel;
        }

        if (strlen($word) == $ancho) {
            return $word;
        } else if (strlen($word) < $ancho) {
            return $this->textCenterAux($word, $ancho);
        }

        $result = '';
        $nword = '';
        foreach (explode(' ', $word) as $aux) {
            if ($nword == '') {
                $nword = $aux;
            } else if (strlen($nword) + strlen($aux) + 1 <= $ancho) {
                $nword = $nword . ' ' . $aux;
            } else {
                if ($result != '') {
                    $result .= "\n";
                }

                $result .= $this->textCenterAux($nword, $ancho);
                $nword = $aux;
            }
        }
        if ($nword != '') {
            if ($result != '') {
                $result .= "\n";
            }

            $result .= $this->textCenterAux($nword, $ancho);
        }

        return $result;
    }

    private function textCenterAux($word = '', $ancho = 40)
    {
        $symbol = " ";
        $middle = round($ancho / 2);
        $length_word = strlen($word);
        $middle_word = round($length_word / 2);
        $last_position = $middle + $middle_word;
        $number_of_spaces = $middle - $middle_word;
        $result = sprintf("%'{$symbol}{$last_position}s", $word);
        for ($i = 0; $i < $number_of_spaces; $i++) {
            $result .= "$symbol";
        }
        return $result;
    }

    protected function priceFormat($val, $decimales = 2, $moneda = false)
    {
        $val = sqrt($val ** 2);
        if ($moneda) {
            return '$ ' . number_format($val, $decimales, '.', '');
        }
        return number_format($val, $decimales, '.', '');
    }

    public function paperCut()
    {
        if ($this->comandoCorte) {
            $aux = explode('.', $this->comandoCorte);
            if ($aux) {
                foreach ($aux as $a) {
                    $this->ticket .= chr($a);
                }

                $this->addLineBreak();
            }
        } 
    }

    public function drawer()
    {
        if (!$this->sinComandos) {            
            $aux = explode('.', $this->comandoApertura);
            if ($aux) {
                foreach ($aux as $a) {
                    $this->ticket .= chr($a);
                }

                $this->ticket .= "\n";
            }            
        } 
    }
}