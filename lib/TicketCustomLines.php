<?php 

class TicketCustomLines
{
	private $position;
    private $documentType;

	public function __construct($documentType = '', $position = '')
	{
        $this->documentType = $documentType; 
		$this->position = $position;       
	}

    public function saveCustomLine($text, $id = false)
    {
        if ($text != '') {
            $customLine = (new ticket_custom_line())->get($id);

            if (!$customLine) {
                $customLine = new ticket_custom_line;
            }

            $customLine->documento = $this->documentType;
            $customLine->texto = $text;
            $customLine->posicion = $this->position;
            $customLine->save();              

            return true;
        }

        return false;       
    }

	public function saveCustomLines($data)
	{
		$customLines = array_filter($data);

		if ($customLines) {
            (new ticket_custom_line())->clean_from_document($this->documentType, $this->position);
            
            foreach ($data as $line) {
                if ($line != '') {
                    $customLine = new ticket_custom_line();

                    $customLine->documento = $this->documentType;
                    $customLine->texto = $line;
                    $customLine->posicion = $this->position;

                    $customLine->save();
                }                
            }

            return true;
        }

        return false;		
	}

    public function deleteCustomLine($id)
    {
        $customLine = (new ticket_custom_line())->get($id);

        if ($customLine) {
                return $customLine->delete();
        }
    }

    public function getLines()
    {
        $customLine = new ticket_custom_line();
        $customLines = $customLine->all_from_document($this->documentType, $this->position);
        
        return $customLines;
    }
}