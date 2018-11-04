<?php
namespace FacturaScripts\model;

require_once __DIR__.'/../../extras/TicketBuilderTrait.php';

class ticket_builder extends \fs_model
{
    use \TicketBuilderTrait;

    public function __construct($terminal = null, $comandos = false) 
    {
        $this->ticket = '';

        $this->anchoPapel = ($terminal->anchopapel) ? $terminal->anchopapel : '45';        
        $this->comandoCorte = ($terminal->comandocorte) ? $terminal->comandocorte : '27.105';
        $this->comandoApertura = ($terminal->comandoapertura) ? $terminal->comandoapertura : '27.112.48';
        $this->sinComandos = $comandos;
    }


    /**
     * Esta función sirve para eliminar los datos del objeto de la base de datos
     */
    public function delete()
    {
        return true;
    }

    /**
     * Esta función devuelve TRUE si los datos del objeto se encuentran
     * en la base de datos.
     */
    public function exists()
    {
        return true;
    }

    /**
     * Esta función sirve tanto para insertar como para actualizar
     * los datos del objeto en la base de datos.
     */
    public function save()
    {
        return true;
    }
}
