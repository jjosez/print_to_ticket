<?php 
//require_once 'plugins/print_to_ticket/model/core/ticket_printer.php';

/**
* Clase para imprimir tickets de facturas.
* Si requieres personalizar tu ticket es esta clase la que necesitas modificar.
*/
class ticket_builder_pedido extends ticket_builder
{
    public function __construct($terminal = null, $comandos = false) 
    {
        parent::__construct($terminal, $comandos);
    }
}