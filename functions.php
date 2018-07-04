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