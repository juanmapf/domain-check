<?php namespace App\Controllers;

class Upload extends BaseController {

    public function index()
    {
            helper(['form', 'url']);
            echo view('upload_csv');
    }

    /**
     * Executes a file "upload" (the file is not actually stored). All csv file
     * lines are extracted and analyzed, checking for domain validity through a regex.
     * Sends both the full domain array and the array count to the view.
     * 
     * @author Juan Manuel Pérez <juanma@kiranalabs.mx>
     */ 
    public function do_upload()
    {
        $domain_pattern = '/^((?!-)[A-Za-z0-9-]{1,63}(?<!-)\\.)+[A-Za-z]{2,6}$/';
        $view = \Config\Services::renderer();
        if ($this->request->getMethod() === 'post')
        {
            $file = $this->request->getFile('userfile');

            if (!$file->isValid())
            {
                $data = [
                    'upload_data' => array(),
                    'upload_count' => 0
                ];
                echo view('upload_success', $data);
                return;
            }

            $handle = $file->openFile('r');
            $rows = array();
            $count = 0;
            $disallowed_extensions = array('html', 'pdf', 'jsx', 'js', 'csv', 'css');
            while(!$handle->eof()) {
                $row = $handle->fgetcsv();

                if (preg_match($domain_pattern, $row[0]) && !in_array(end(preg_split('/\./', $row[0])), $disallowed_extensions))
                {
                    array_push($rows, [
                        'domain' => $row[0],
                    ]);
                }
            }
            $data = [
                'upload_data' => $rows,
                'upload_count' => count($rows)
            ];
            echo view('upload_success', $data);
        } else {
            echo view('upload_success');
        }
    }

    /**
     * Checks domain availability of all hosts received. Uses the IPv4 method to
     * define ownership. This method is nor reliable and should be changed to a
     * robust external API or directly to whois server lookups.
     * 
     * @author Juan Manuel Pérez <juanma@kiranalabs.mx>
     */
    public function get_availability()
    {
        putenv('RES_OPTIONS=retrans:1 retry:1 timeout:0.350 attempts:1');
        if ($this->request->getMethod() === 'post')
        {
            $hosts = $this->request->getPost('hosts');
            $availability = array();
            foreach($hosts as $index => $host)
            {
                if (gethostbyname($host['domain'].'.') == $host['domain'].'.')
                {
                    array_push($availability, [
                        'host' => $host,
                    ]);
                }
            }
            echo json_encode($availability);
        }
    }

    /**
     * Checks domain authority of all hosts received through the OpenRank API.
     * Check https://openrank.io/ for more info. Executes the request through
     * cURL.
     * 
     * @author Juan Manuel Pérez <juanma@kiranalabs.mx>
     */ 
    public function get_authority()
    {
        if ($this->request->getMethod() === 'post')
        {
            $domains = $this->request->getPost('domains');
            $curl = curl_init();
            $setopt_array = array(
                CURLOPT_URL => 'https://api.openrank.io/?key=dxCyoxuox/6QlOxe/Lq6s7LdEi2oWDKtD5q5XoRlf/M&d='.$domains,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array()
            );
            curl_setopt_array($curl, $setopt_array);
            $json_response = curl_exec($curl);
            echo json_encode($json_response);
            curl_close($curl);
        }
    }
}
?>