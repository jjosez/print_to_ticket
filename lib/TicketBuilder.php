<?php 
require_once 'plugins/print_to_ticket/extras/TicketWriter.php';

/**
* Clase pare imprimir tickets.
*/
class TicketBuilder
{
    use TicketWriter;

    private $ticket;
    private $anchoPapel;
    private $sinComandos;

    private $document;
    private $documentType;
    private $empresa;

    public function __construct($terminal = null, $comandos = false) 
    {
        $this->ticket = '';

        $this->anchoPapel = ($terminal->anchopapel) ? $terminal->anchopapel : '45';        
        $this->comandoCorte = ($terminal->comandocorte) ? $terminal->comandocorte : '27.105';
        $this->sinComandos = $comandos;

        //$this->writeTicketHeader($empresa);
        //$this->writeTicketBody($document, $documentType);
        //$this->writeTicketFooter($document, $leyenda);
    }

    public function writeCompanyBlock($empresa)
    {
        $this->addBreakLine();

        $this->addSplitter();
        $this->addText($empresa->nombrecorto, true, true);
        $this->addBigText($empresa->direccion, true, true);

        if ($empresa->telefono) {
            $this->addText('TEL: ' . $empresa->telefono, true, true);
        }

        $this->addBreakLine();
        $this->addText($empresa->nombre, true, true);
        $this->addText(FS_CIFNIF . ': ' . $empresa->cifnif, true, true);
        $this->addSplitter('=');
    }

    public function writeHeaderBlock($headerLines)
    {
        foreach ($headerLines as $line) {
            $this->addText($line, true, true);
        }

        $this->addBreakLine();
    }



    public function writeBodyBlock($document, $documentType)
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
        $this->addLabelValue('TOTAL DEL DOCUMENT:',$this->priceFormat($document->total));
    }

    public function writeFooterBlock($footerLines, $leyenda, $codigo)
    {
        $this->addBreakLine(2);

        foreach ($footerLines as $line) {
            $this->addText($line, true, true);
        }

        $this->addText($leyenda, true, true);
        $this->addBarcode($codigo);
    }

    public function toString()
    {
        $this->addBreakLine(4);
        $this->paperCut();
        
        return $this->ticket;
    }
}