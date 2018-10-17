<?php 
require_once 'plugins/print_to_ticket/lib/TicketBuilder.php';

/**
* Clase para imprimir tickets de albaranes.
* Si requieres personalizar tu ticket es esta clase la que necesitas modificar.
*/
class TicketBuilderPedido extends TicketBuilder
{
    public function __construct($terminal = null, $comandos = false) 
    {
        parent::__construct($terminal, $comandos);
    }
}