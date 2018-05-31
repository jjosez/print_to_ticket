<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014-2016  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of pago
 *
 * @author Carlos García Gómez
 */
class ticket_print_job extends fs_model
{
    public $id;
    public $tipo;
    public $texto;

    public function __construct($data = false)
    {
        parent::__construct('ticketprintjob');

        if ($data) {
            $this->id = $this->intval($data['id']);
            $this->tipo = $data['tipo'];
            $this->texto = $data['texto'];
        } else {
            $this->id = null;
            $this->tipocomprobante = 'albaran';
            $this->texto = '';
        }
    }

    protected function install()
    {
        return '';
    }

    public function get_print_job($tipo)
    {
        $data = $this->db->select("SELECT * FROM ticketprintjob WHERE tipo = " 
            . $this->var2str($tipo) . ";");

        if ($data) {
            return new ticket_print_job($data[0]);
        } else {
            return false;
        }
    }

    public function exists()
    {
        if (is_null($this->id)) {
            return false;
        } else {
            return $this->db->select("SELECT * FROM ticketprintjob WHERE id = " . $this->var2str($this->id) . ";");
        }
    }

    public function save()
    {
        #this->texto = $this->no_html($this->nota);

        if ($this->exists()) {
            $sql = "UPDATE ticketprintjob SET texto = " . $this->var2str($this->texto) .
            "  WHERE id = " . $this->var2str($this->id) . ";";
            
        } else {
            $sql = "INSERT INTO ticketprintjob (texto,tipo) VALUES ("
                . $this->var2str($this->texto) . ","
                . $this->var2str($this->tipo) . ");";
        }
        return $this->db->exec($sql);
    }

    public function delete()
    {
        return TRUE;
    }
}
