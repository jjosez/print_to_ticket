<?php 
require_once 'plugins/print_to_ticket/extras/TicketWriter.php';

/**
* Clase pare imprimir tickets.
*/
class TicketBuilder
{
    use TicketWriter;

    protected $ticket;
    protected $anchoPapel;
    protected $sinComandos;

    protected $document;
    protected $documentType;
    protected $empresa;

    protected $headerLines;
    protected $footerLines;
    protected $footerText;

    public function __construct($terminal = null, $comandos = false) 
    {
        $this->ticket = '';

        $this->anchoPapel = ($terminal->anchopapel) ? $terminal->anchopapel : '45';        
        $this->comandoCorte = ($terminal->comandocorte) ? $terminal->comandocorte : '27.105';
        $this->comandoApertura = ($terminal->comandoapertura) ? $terminal->comandoapertura : '27.112.48';
        $this->sinComandos = $comandos;
    }

    public function setEmpresa($empresa)
    {
        $this->empresa = $empresa;
    }

    public function setDocumento($document, $documentType)
    {
        $this->document = $document;
        $this->documentType = $documentType;
    }

    public function setCostumHeaderLines($headerLines)
    {
        foreach ($headerLines as $line) {
            $this->headerLines[] = $line->texto;
        }
    }

    public function setCostumFooterLines($footerLines)
    {
        foreach ($footerLines as $line) {
            $this->footerLines[] = $line->texto;
        }
    }

    public function setFooterText($footerText)
    {
        $this->footerText = $footerText;
    }

    protected function writeCompanyBlock($empresa)
    {
        $this->addLineBreak();

        $this->addSplitter();
        $this->addText($empresa->nombrecorto, true, true);
        $this->addBigText($empresa->direccion, true, true);

        if ($empresa->telefono) {
            $this->addText('TEL: ' . $empresa->telefono, true, true);
        }

        $this->addLineBreak();
        $this->addText($empresa->nombre, true, true);
        $this->addText(FS_CIFNIF . ': ' . $empresa->cifnif, true, true);
        $this->addSplitter('=');
    }

    protected function writeHeaderBlock($headerLines)
    {
        if ($headerLines) {
            foreach ($headerLines as $line) {
                $this->addText($line, true, true);
            }
        }               
    }

    protected function writeBodyBlock($document, $documentType)
    {
        $text = strtoupper($documentType) . ' ' . $document->codigo;
        $this->addText($text, true, true);

        $text = $document->fecha . ' ' . $document->hora;
        $this->addText($text, true, true);

        $this->addText("CLIENTE: " . $document->nombrecliente);
        $this->addSplitter('=');
        $this->addLabelValue('REFERENCIA','CANTIDAD');

        $totaliva=0;
        foreach ($document->get_lineas() as $linea) {
            $this->addSplitter('=');
            $this->addLabelValue($linea->referencia,$linea->cantidad);
            $this->addText($linea->descripcion);
            $this->addLabelValue('PVP:',$this->priceFormat($linea->pvpunitario));
            $this->addLabelValue('IMPORTE:',$this->priceFormat($linea->pvptotal)); 

            $totaliva += $linea->pvptotal * $linea->iva / 100;            
        }

        $this->addSplitter('=');
        $this->addLabelValue('IVA',$this->priceFormat($totaliva));
        $this->addLabelValue('TOTAL DEL DOCUMENTO:',$this->priceFormat($document->total));
    }

    protected function writeFooterBlock($footerLines, $leyenda, $codigo)
    {
        $this->addLineBreak(2);

        if ($footerLines) {
            foreach ($footerLines as $line) {
                $this->addText($line, true, true);
            }
        }

        $this->addText($leyenda, true, true);
        $this->addBarcode($codigo);
    }

    public function toString($open = false) : string
    {
        if ($open) {
            $this->drawer();
        }
        
        $this->writeCompanyBlock($this->empresa);
        $this->writeHeaderBlock($this->headerLines); 
        $this->writeBodyBlock($this->document, $this->documentType); 
        $this->writeFooterBlock($this->footerLines, $this->footerText, $this->document->codigo);      

        $this->addLineBreak(4);
        $this->paperCut();
        
        return $this->ticket;
    }

    public function openDrawer() : string
    {
        $this->drawer();
        
        return $this->ticket;
    }
}