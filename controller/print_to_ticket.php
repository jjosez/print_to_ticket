<?php

/*
 * @author Juan Jose Prieto Dzul     juanjoseprieto88@gmail.com
 * @copyright 2017, 2018. All Rights Reserved.
 */

/**
 * Description of printo_to_ticket
 * 
 * Plugin que permite imprimir directamente a una impresora de tickets albaran, factura, pedido, servicio
 * utilizando la aplicacion de remote_printer.
 *
 * @author Juan Jose Prieto Dzul
 */
require_once 'plugins/facturacion_base/extras/fbase_controller.php';

require_once 'plugins/print_to_ticket/lib/TicketCustomLines.php';
require_once 'plugins/print_to_ticket/lib/TicketBuilderAlbaran.php';
require_once 'plugins/print_to_ticket/lib/TicketBuilderFactura.php';
require_once 'plugins/print_to_ticket/lib/TicketBuilderPedido.php';
require_once 'plugins/print_to_ticket/lib/TicketBuilderServicio.php';

class print_to_ticket extends fbase_controller
{
    public $mensaje;
    public $terminales;
    public $settings;

    public $headerLines;
    public $footerLines;

    public $documentType;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Configurar ticket', 'admin');
    }

    protected function private_core()
    {
        $this->shareExtensions(); 
        $this->loadSettings();

        $this->headerLines = $this->loadCustomLines('general', 'header');
        $this->footerLines = $this->loadCustomLines('general', 'footer');

        $this->terminales = (new terminal_caja())->all();       

        if ($this->settings['print_job_terminal'] != '') {
            $terminal = (new terminal_caja())->get($this->settings['print_job_terminal']);
        } else {
            $this->new_advice('Es necesario seleccionar una terminal.');
            return;
        }

        // $this->documentType = isset($_GET['tipo']) ?  $_GET['tipo'] : null; 
        $this->documentType = filter_input(INPUT_GET, 'tipo'); 
        $documentType = $this->documentType;      

        if ($documentType) {
            $this->template = 'print_screen';
            switch ($documentType) {
                case 'factura':
                    $document = (new factura_cliente())->get($_GET['id']);                    
                    break;

                case 'albaran':
                    $document = (new albaran_cliente())->get($_GET['id']);                    
                    break;

                case 'pedido':
                    $document = (new pedido_cliente())->get($_GET['id']);                    
                    break;

                case 'servicio':
                    $document = (new servicio_cliente())->get($_GET['id']);                    
                    break;

                default:
                    # code...
                    break;
            }

            $this->buildTicket($document, $documentType, $terminal);
            return;
        }

        $documentType = filter_input(INPUT_POST, 'opcion', FILTER_DEFAULT, FILTER_NULL_ON_FAILURE);
        $position = filter_input(INPUT_POST, 'posicion', FILTER_DEFAULT, FILTER_NULL_ON_FAILURE);

        if ($documentType) {
            if ($documentType == 'general' && !$position) {
                $this->saveSettings();                
            }

            switch ($position) {
                case 'header':
                    $this->saveCustomLines($documentType, $position);
                    $this->headerLines = $this->loadCustomLines($documentType, $position);
                    break;

                case 'footer':
                    $this->saveCustomLines($documentType, $position);
                    $this->footerLines = $this->loadCustomLines($documentType, $position);
                    break;
                
                default:
                    # code...
                    break;
            }
        }
    }

    private function buildTicket($document, $documentType,$terminal = null)
    {
        $this->mensaje = 'Imprimiendo ' . strtolower($documentType) 
                        . ' ' . $document->codigo;

        switch ($documentType) {
            case 'factura':
                $ticket = new TicketBuilderFactura($terminal);
                break;

            case 'albaran':
                $ticket = new TicketBuilderAlbaran($terminal);
                break;

            case 'servicio':
                $ticket = new TicketBuilderServicio($terminal);
                break;
            
            default:
                # code...
                break;
        }

        $ticket->setEmpresa($this->empresa);
        $ticket->setDocumento($document, $documentType);
        $ticket->setCostumHeaderLines($this->headerLines); 
        $ticket->setCostumFooterLines($this->footerLines);      
        $ticket->setFooterText($this->settings['print_job_text']);

        $print_job = (new ticket_print_job())->get_print_job($documentType);
        if (!$print_job) {
            $print_job = new ticket_print_job();
            $print_job->tipo = $documentType;
        }

        $print_job->texto .= $ticket->toString();
        $print_job->save();
    }

    private function saveCustomLines($documentType, $position)
    {
        $texto = filter_input(INPUT_POST, 'texto', FILTER_DEFAULT);
        $action = filter_input(INPUT_POST, 'accion',FILTER_DEFAULT);
        $id = filter_input(INPUT_POST, 'id',FILTER_DEFAULT, FILTER_NULL_ON_FAILURE);

        $customLines = new TicketCustomLines($documentType, $position);

        if ($action == 'borrar') {
            $customLines->deleteCustomLine($id);
        } else {
            $customLines->saveCustomLine($texto, $id);
        }
    }

    public function loadCustomLines($documentType, $position)
    {
        return (new TicketCustomLines($documentType, $position))->getLines();
    }

    private function loadSettings()
    {
        $fsvar = new fs_var();
        $this->settings = array(
            'print_job_terminal' => '',
            'print_job_text' => '',
        );
        $this->settings = $fsvar->array_get($this->settings, false);
    }

    private function saveSettings()
    {
        $fsvar = new fs_var();

        $this->settings['print_job_terminal'] = $_POST['print_job_terminal'];
        $this->settings['print_job_text'] = $_POST['print_job_text'];

        if ($fsvar->array_save($this->settings)) {
            $this->new_message('Datos guardados correctamente.');
        } else {
            $this->new_error_msg('Error al guardar los datos.');
        }
    }

    private function shareExtensions()
    {
        $extensiones = array(
            array(
                'name' => 'factura_ticket_print_job',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_factura',
                'type' => 'modal',
                'text' => '<i class="fa fa-print" aria-hidden="true"></i>'
                . '<span class="hidden-xs">&nbsp; Ticket</span>',
                'params' => '&tipo=factura',
            ),
            array(
                'name' => 'albaran_ticket_print_job',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_albaran',
                'type' => 'modal',
                'text' => '<i class="fa fa-print" aria-hidden="true"></i>'
                . '<span class="hidden-xs">&nbsp; Ticket</span>',
                'params' => '&tipo=albaran',
            ),
            array(
                'name' => 'pedido_ticket_print_job',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_pedido',
                'type' => 'modal',
                'text' => '<i class="fa fa-print" aria-hidden="true"></i>'
                . '<span class="hidden-xs">&nbsp; Ticket</span>',
                'params' => '&tipo=pedido',
            ),
            array(
                'name' => 'presupuesto_ticket_print_job',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_presupuesto',
                'type' => 'modal',
                'text' => '<i class="fa fa-print" aria-hidden="true"></i>'
                . '<span class="hidden-xs">&nbsp; Ticket</span>',
                'params' => '&tipo=presupuesto',
            ),
            array(
                'name' => 'servicio_ticket_print_job',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_servicio',
                'type' => 'modal',
                'text' => '<i class="fa fa-print" aria-hidden="true"></i>'
                . '<span class="hidden-xs">&nbsp; Ticket</span>',
                'params' => '&tipo=servicio',
            ),
        );

        # añadimos/actualizamos las extensiones
        foreach ($extensiones as $ext) {
            $fsext = new fs_extension($ext);
            if (!$fsext->save()) {
                $this->new_error_msg('Imposible guardar la extensión ' . $ext['name'] . '.');
            }
        }

        # eliminamos las que sobran
        $fsext = new fs_extension();

        foreach ($fsext->all_from(__CLASS__) as $ext) {
            $encontrada = false;

            foreach ($extensiones as $ext2) {
                if ($ext->name == $ext2['name']) {
                    $encontrada = true;
                    break;
                }
            }

            if (!$encontrada) {
                $ext->delete();
            }
        }
    }
}
