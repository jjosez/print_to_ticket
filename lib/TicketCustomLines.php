<?php 

class TicketCustomLines
{
	private $position;

	public function __construct($position)
	{
		$this->position = $position;
	}

	public function saveCustomLines($data)
	{
		$customLines = array_filter($data);

		if ($customLines) {
            $this->storeCustomLines(json_encode($customLines));
            return true;
        }

        return false;		
	}

	public function storeCustomLines($customLines)
    {
        $path = 'tmp/' . FS_TMP_NAME . 'ticket/template/';

        if (!file_exists($path)) {
            @mkdir($path, 0777, true);
        }

        file_put_contents($path . $this->position . '.json', $customLines);
    }

    public function getLines()
    {
        $path = 'tmp/' . FS_TMP_NAME . 'ticket/template/';

        if (!file_exists($path . $this->position . '.json')) {
            if (!file_exists($path)) {
                @mkdir($path, 0777, true);
            }

            file_put_contents($path . $this->position . '.json', '');

            return array();
        }

        $data = file_get_contents($path . $this->position . '.json');

        if ($data === null) {
            return array();
        }

        return json_decode($data);
    }
}