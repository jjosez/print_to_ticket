<?php

/*
 * @author Juan Jose Prieto Dzul     juanjoseprieto88@gmail.com
 * @copyright 2016, XXXX. All Rights Reserved.
 */

/**
 * Description of printo_to_ticket
 *
 * @author Juan Jose Prieto Dzul
 */
require_once 'plugins/facturacion_base/extras/fbase_controller.php';
require_once 'plugins/print_to_ticket/lib/TicketBuilder.php';
require_once 'plugins/print_to_ticket/lib/TicketCustomLines.php';

class print_to_ticket extends fbase_controller
{
    public $mensaje;
    public $documentType;
    public $terminales;
    public $settings;

    public $headerLines;
    public $footerLines;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Configurar ticket', 'admin');
    }

    protected function private_core()
    {
        $this->shareExtensions(); 
        $this->loadSettings();

        $this->headerLines = $this->loadCustomLines('header');
        $this->footerLines = $this->loadCustomLines('footer');

        $this->terminales = (new terminal_caja())->all();       

        if ($this->settings['print_job_terminal'] != '') {
            $terminal = (new terminal_caja())->get($this->settings['print_job_terminal']);
        } else {
            $this->new_message('Es necesario seleccionar una terminal.');
        }

        //$this->documentType = isset($_GET['tipo']) ?  $_GET['tipo'] : null; 
        $this->documentType = filter_input(INPUT_GET, 'tipo');       

        if ($this->documentType) {
            $this->template = 'print_screen';
            switch ($this->documentType) {
                case 'factura':
                    $document = (new factura_cliente())->get($_GET['id']);                    
                    break;

                case 'albaran':
                    $document = (new albaran_cliente())->get($_GET['id']);                    
                    break;

                case 'pedido':
                    $document = (new pedido_cliente())->get($_GET['id']);                    
                    break;

                default:
                    # code...
                    break;
            }

            $this->buildTicket($document, $terminal);
            return;
        }

        $option = filter_input(INPUT_GET, 'opcion');

        if ($option) {
            switch ($option) {
                case 'settings':
                    $this->saveSettings();
                    break;

                case 'header':
                    $this->saveCustomHeaderLines();
                    break;

                case 'footer':
                    $this->saveCustomFooterLines();
                    break;
                
                default:
                    # code...
                    break;
            }
        }
    }

    private function buildTicket($document, $terminal = null)
    {
        $this->mensaje = 'Imprimiendo ' . strtolower($this->documentType) 
                        . ' ' . $document->codigo;

        $ticket = new TicketBuilder($terminal);

        $ticket->writeCompanyBlock($this->empresa);
        $ticket->writeHeaderBlock($this->headerLines); 
        $ticket->writeBodyBlock($document, $this->documentType); 
        $ticket->writeFooterBlock(
            $this->footerLines,
            $this->settings['print_job_text'],
            $document->codigo
        );      

        $print_job = (new ticket_print_job())->get_print_job($this->documentType);
        if (!$print_job) {
            $print_job = new ticket_print_job();
            $print_job->tipo = $this->documentType;
        }

        $print_job->texto .= $ticket->toString();
        $print_job->save();
    }

    private function saveCustomHeaderLines()
    {
        $data = filter_input(INPUT_POST, 'fields', FILTER_DEFAULT , FILTER_REQUIRE_ARRAY);

        $customLines = new TicketCustomLines('header');
        $customLines->saveCustomLines($data);
    }

    private function saveCustomFooterLines()
    {
        $data = filter_input(INPUT_POST, 'fields', FILTER_DEFAULT , FILTER_REQUIRE_ARRAY);

        $customLines = new TicketCustomLines('footer');
        $customLines->saveCustomLines($data);
    }

    private function loadCustomLines($position)
    {
        return (new TicketCustomLines($position))->getLines();
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
