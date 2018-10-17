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
 * @author Juan Jose Prieto Dzul
 */
class ticket_custom_line extends fs_extended_model
{
    public $id;
    public $documento;
    public $posicion;    
    public $texto;

    public function __construct($data = false)
    {
        parent::__construct('ticketcustomline', $data);
    }

    public function model_class_name()
    {
        return 'ticket_custom_line';
    }

    public function primary_column()
    {
        return 'id';
    }

    public function all_from_document($documento, $posicion = false)
    {
        $lines = array();

        $sql = 'SELECT * FROM ' . $this->table_name() . ' WHERE documento = '
            . $this->var2str($documento);

        if ($posicion) {
            $sql .= ' AND posicion = ' . $this->var2str($posicion);
        }

        $sql .= ';';
        $data = $this->db->select($sql);

        if ($data) {
            foreach ($data as $l) {
                $lines[] = new ticket_custom_line($l);
            }
        }

        return $lines;
    }

    public function clean_from_document($documento, $posicion)
    {
        $lines = array();

        $sql = 'DELETE FROM ' . $this->table_name() 
            . ' WHERE documento = ' . $this->var2str($documento) 
            . ' AND posicion = ' . $this->var2str($posicion) . ';';

        return $this->db->exec($sql);
    }
}
