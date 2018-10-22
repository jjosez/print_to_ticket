<?php 

if (!function_exists('remote_printer')) {

    /**
     * Vuelca en la salida estándar el buffer de tickets pendientes de imprimir.
     */
    function remote_printer()
    {
        if (isset($_REQUEST['documento'])) {
            $print_job = (new ticket_print_job())->get_print_job($_REQUEST['documento']);
            
            if ($print_job) {
                echo $print_job->texto;

                $print_job->texto = '';
                $print_job->save();
            } else {
                echo 'ERROR: documento no valido.';
            }
        } elseif (isset($_REQUEST['terminal'])) {
            $t0 = new terminal_caja();
            $terminal = $t0->get($_REQUEST['terminal']);
            
            if ($terminal) {
                echo $terminal->tickets;

                $terminal->tickets = '';
                $terminal->save();
            } else {
                echo 'ERROR: terminal no encontrado.';
            }
        }

    }
}

if (!function_exists('fs_print_job')) {
    require_once 'plugins/print_to_ticket/lib/TicketCustomLines.php';
    require_once 'plugins/print_to_ticket/lib/TicketBuilderAlbaran.php';
    require_once 'plugins/print_to_ticket/lib/TicketBuilderFactura.php';
    require_once 'plugins/print_to_ticket/lib/TicketBuilderPedido.php';
    require_once 'plugins/print_to_ticket/lib/TicketBuilderServicio.php';

    /**
     * Agrega un nuevo trabajo a la cola de impresion por tipo de documento.
     */
    function fs_print_job($documentType, $documentId, $terminal, $empresa = false, $open = false)
    {  

        if ($documentType) {
            switch ($documentType) {
                case 'factura':
                    $document = (new factura_cliente())->get($documentId);                    
                    break;

                case 'albaran':
                    $document = (new albaran_cliente())->get($documentId);                    
                    break;

                case 'pedido':
                    $document = (new pedido_cliente())->get($documentId);                    
                    break;

                case 'servicio':
                    $document = (new servicio_cliente())->get($documentId);                    
                    break;

                default:
                    $document = false;
                    break;
            }
        }

        if ($document) {
            switch ($documentType) {
                case 'albaran':
                    $ticket = new TicketBuilderAlbaran($terminal);
                    break;

                case 'factura':
                    $ticket = new TicketBuilderFactura($terminal);
                    break;

                case 'pedido':
                    $ticket = new TicketBuilderPedido($terminal);
                    break;

                case 'servicio':
                    $ticket = new TicketBuilderServicio($terminal);
                    break;
                
                default:
                    # code...
                    break;
            }

            $customLine = new ticket_custom_line();
            $headerLines = $customLine->all_from_document($documentType, 'header');
            $footerLines = $customLine->all_from_document($documentType, 'footer');

            $fsvar = new fs_var();
            //$terminal = (new terminal_caja())->get($fsvar->simple_get('print_job_terminal'));
            $footerText = $fsvar->simple_get('print_job_text');

            $ticket->setEmpresa($empresa);
            $ticket->setDocumento($document, $documentType);
            $ticket->setCostumHeaderLines($headerLines); 
            $ticket->setCostumFooterLines($footerLines);      
            $ticket->setFooterText($footerText);

            $print_job = (new ticket_print_job())->get_print_job($documentType);
            if (!$print_job) {
                $print_job = new ticket_print_job();
                $print_job->tipo = $documentType;
            }

            $print_job->texto .= $ticket->toString($open);
            $print_job->save();
        }
    }
}

if (!function_exists('fs_abrir_cajon')) {
    require_once 'plugins/print_to_ticket/lib/TicketBuilder.php';
    /**
     * Agrega un nuevo trabajo a la cola de impresion por tipo de documento.
     */
    function fs_abrir_cajon($terminal)
    {  
            $ticket = new TicketBuilder($terminal);
            $documentType = 'drawer';

            $print_job = (new ticket_print_job())->get_print_job($documentType);
            if (!$print_job) {
                $print_job = new ticket_print_job();
                $print_job->tipo = $documentType;
            }

            $print_job->texto = $ticket->openDrawer();
            $print_job->save();
    }
}