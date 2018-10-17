<?php 
require_once 'plugins/print_to_ticket/lib/TicketBuilder.php';

/**
* Clase para imprimir tickets de servicios.
* Si requieres personalizar tu ticket es esta clase la que necesitas modificar.
*/
class TicketBuilderServicio extends TicketBuilder
{
    private $settings;

    public function __construct($terminal = null, $comandos = false) 
    {
        parent::__construct($terminal, $comandos);

        $fsvar = new fs_var();
        $this->settings = $fsvar->array_get(
            array(
                'st_servicio' => "Servicio",
                'st_servicios' => "Servicios",
                'st_material' => "Material",
                'st_material_estado' => "Estado del material entregado"
            ), FALSE 
        );
    }

    public function writeBodyBlock($document, $documentType)
    {
        $text = strtoupper($documentType) . ' ' . $document->codigo;
        $this->addText($text, true, true);

        $text = $document->fecha . ' ' . $document->hora;
        $this->addText($text, true, true);

        $cliente = (new cliente)->get($document->codcliente);
        ;//telefono2;

        $this->addText("CLIENTE: " . $document->nombrecliente);
        $this->addText("TEL. CLIENTE: " . $cliente->telefono1);

        $this->addSplitter('=');
        $this->addMultiLineText($this->settings['st_material'] . ' ' . $document->material);
        $this->addBigText('ESTADO DEL MATERIAL: ' . $document->material_estado,true);
        $this->addBigText('ACCESORIOS: ' . $document->accesorios,true);
        $this->addBigText('DESCRIPCION: ' . $document->descripcion,true);        

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
}