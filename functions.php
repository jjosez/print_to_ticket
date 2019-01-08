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

            $customLine = new ticket_custom_line();
            $headerLines = $customLine->all_from_document($documentType, 'header');
            $footerLines = $customLine->all_from_document($documentType, 'footer');

            $fsvar = new fs_var();
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
    /**
     * Manda un comando para abrir cajon por medio de la impresora de tickets.
     */
    function fs_abrir_cajon($terminal)
    {  
        $ticket = new ticket_builder($terminal);
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