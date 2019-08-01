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
        $terminal = false;       

        if ($this->settings['print_job_terminal'] != '') {
            $terminal = (new terminal_caja())->get($this->settings['print_job_terminal']);
        } else {
            $this->new_message('Selecciona una terminal para poder imprimir.');
        }

        // $this->documentType = isset($_GET['tipo']) ?  $_GET['tipo'] : null; 
        $this->documentType = filter_input(INPUT_GET, 'tipo'); 
        $documentType = $this->documentType;      

        if ($documentType) {
            $this->template = 'print_screen';

            if (!$terminal) {
                $this->new_advice('No se ha podido imprimir, es necesario seleccionar una terminal.');
                return;
            }
            
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
            case 'albaran':
                $ticket = new ticket_builder_albaran($terminal);
                break;

            case 'factura':
                $ticket = new ticket_builder_factura($terminal);
                break;
            
            case 'pedido':
                $ticket = new ticket_builder_pedido($terminal);
                break;

            case 'servicio':
                $ticket = new ticket_builder_servicio($terminal);
                break;
            
            default:
                # code...
                break;
        }

        if (isset($ticket)) {

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
    }

    private function saveCustomLines($documentType, $position)
    {
        $texto = filter_input(INPUT_POST, 'texto', FILTER_DEFAULT);
        $action = filter_input(INPUT_POST, 'accion',FILTER_DEFAULT);
        $id = filter_input(INPUT_POST, 'id',FILTER_DEFAULT, FILTER_NULL_ON_FAILURE);

        $customLine = (new ticket_custom_line)->get($id);

        if ($customLine) {
            if ($action == 'borrar') {
                if ($customLine->delete()) {
                    $this->new_message('Linea borrada corrrectamente.');
                } else {
                    $this->new_error_msg('No se pudo eliminar la linea.');
                }
            } else {
                $customLine->documento = $documentType;
                $customLine->texto = $texto;
                $customLine->posicion = $position;

                $customLine->save();
            }
        } else {
            if ($action == 'guardar') {
                $customLine = new ticket_custom_line();

                $customLine->documento = $documentType;
                $customLine->texto = $texto;
                $customLine->posicion = $position;

                if ($customLine->save()) {
                    $this->new_message('Linea guardada corrrectamente.');
                    return;
                }

                $this->new_error_msg('No se encontro la linea.');
            }
        }        
    }

    public function loadCustomLines($documentType, $position)
    {
        return (new ticket_custom_line)->all_from_document($documentType, $position);
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
