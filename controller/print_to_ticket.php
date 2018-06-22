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
require_once 'plugins/print_to_ticket/lib/TicketWriter.php';

class print_to_ticket extends fbase_controller
{
    public $mensaje;
    public $tipo_documento;
    public $terminales;
    public $terminal;
    public $config;


    public $factura;
    public $albaran;
    public $pedido;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Imprimir a ticket', 'admin');
    }

    protected function private_core()
    {
        $this->share_extensions(); 
        $this->load_config();
        $this->terminales = (new terminal_caja())->all();       

        $this->tipo_documento = isset($_GET['tipo']) ?  $_GET['tipo'] : null;

        if ($this->config['print_job_terminal'] != '') {
            $this->terminal = (new terminal_caja())->get($this->config['print_job_terminal']);
        } else {
            $this->new_message('Es necesario seleccionar una terminal.');
        }        

        if (isset($this->tipo_documento)) {
            $this->template = 'print_screen';
            switch ($this->tipo_documento) {
                case 'factura':
                    $documento = (new factura_cliente())->get($_GET['id']);                    
                    break;

                case 'albaran':
                    $documento = (new albaran_cliente())->get($_GET['id']);                    
                    break;

                case 'pedido':
                    $documento = (new pedido_cliente())->get($_GET['id']);                    
                    break;

                default:
                    # code...
                    break;
            }

            $this->print_ticket($documento);
        }

        if (isset($_POST['config'])) {
            $this->save_config();
        }
    }

    private function load_config()
    {
        $fsvar = new fs_var();
        $this->config = array(
            'print_job_terminal' => '',
            'print_job_text' => '',
        );
        $this->config = $fsvar->array_get($this->config, false);
    }

    private function save_config()
    {
        $fsvar = new fs_var();

        $this->config['print_job_terminal'] = $_POST['print_job_terminal'];
        $this->config['print_job_text'] = $_POST['print_job_text'];

        if ($fsvar->array_save($this->config)) {
            $this->new_message('Datos guardados correctamente.');
        } else {
            $this->new_error_msg('Error al guardar los datos.');
        }
    }

    private function print_ticket($documento, $terminal = null)
    {
        $this->mensaje = 'Imprimiendo ' . strtolower($this->tipo_documento) 
                        . ' ' . $documento->codigo;
        
        if ($terminal) {
            $ancho = $terminal->anchopapel;
            $comandocorte = $terminal->comandocorte;
        } else {
            $ancho = 42;
            $comandocorte = null;
        }

        $ticket = new TicketWriter($ancho, $comandocorte);

        $this->print_ticket_encabezado($ticket);

        $ticket->add_text_line(strtoupper($this->tipo_documento) . ' ' . $documento->codigo, true, true);
        $ticket->add_text_line($documento->fecha . ' ' . $documento->hora, true, true);
        $ticket->add_line_break();

        $ticket->add_text_line("CLIENTE: " . $documento->nombrecliente);
        $ticket->add_line_splitter('=');
        $ticket->add_line_label_value('REFERENCIA','CANTIDAD');

        $totaliva=0;
        foreach ($documento->get_lineas() as $linea) {
            $ticket->add_line_splitter();
            $ticket->add_line_label_value($linea->referencia,$linea->cantidad);
            $ticket->add_text_line($linea->descripcion);
            $ticket->add_line_label_value('PVP:',$this->show_numero($linea->pvpunitario));
            $ticket->add_line_label_value('IMPORTE:',$this->show_numero($linea->pvptotal)); 
            $totaliva += $linea->pvptotal * $linea->iva / 100;            
        }

        $ticket->add_line_splitter('=');
        $ticket->add_line_label_value('IVA',$this->show_numero($totaliva));
        $ticket->add_line_label_value('TOTAL DEL DOCUMENTO:',$this->show_numero($documento->total));
        $ticket->add_line_break(2);

        $ticket->add_text_line($this->config['print_job_text'], true, true);
        $ticket->add_bcode_line($documento->codigo);

        $print_job = (new ticket_print_job())->get_print_job($this->tipo_documento);
        if (!$print_job) {
            $print_job = new ticket_print_job();
            $print_job->tipo = $this->tipo_documento;
        }

        $print_job->texto = $ticket->toString();
        $print_job->save();
    }

    private function print_ticket_encabezado(&$ticket)
    {
        $ticket->add_line_break();

        $ticket->add_line_splitter('=');
        $ticket->add_text_line($this->empresa->nombrecorto, true, true);
        $ticket->add_big_text_line($this->empresa->direccion, true, true);

        if ($this->empresa->telefono) {
            $ticket->add_text_line('TEL: ' . $this->empresa->telefono, true, true);
        }

        $ticket->add_line_break();
        $ticket->add_text_line($this->empresa->nombre, true, true);
        $ticket->add_text_line(FS_CIFNIF . ': ' . $this->empresa->cifnif, true, true);
        $ticket->add_line_splitter('=');
    }

    private function share_extensions()
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
