<?php

namespace NewIDC\DirectAdmin;

use NewIDC\Plugin\Server;

class Plugin extends Server
{
    protected $name = 'DirectAdmin';

    protected $composer = 'newidc/directadmin';

    protected $description = 'DirectAdmin对接插件';

    private function createSocket()
    {
        $sock = new HTTPSocket;
        if ($this->server->api_access_ssl) {
            $sock->connect("ssl://" . $this->getHost(), $this->getPort());
        } else {
            $sock->connect($this->getHost(), $this->getPort());
        }
        $sock->set_login($this->server->username, $this->server->password);

        return $sock;
    }

    /**
     * @inheritDoc
     */
    public function activate()
    {
        $sock = $this->createSocket();

        if ($this->product->type == 'reseller') {
            $ip = $this->product->server_configs['reseller_ip'];
            $action = '/CMD_ACCOUNT_RESELLER';
        } else {
            $ip = $this->product->server_configs['dedicated_ip'] ? 'assign' : 'shared';
            $action = '/CMD_API_ACCOUNT_USER';
        }

        $sock->query($action, [
            'action' => 'create',
            'add' => 'Submit',
            'username' => $this->service->username,
            'email' => $this->service->user->email,
            'passwd' => $this->service->password,
            'passwd2' => $this->service->password,
            'domain' => $this->service->domain,
            'package' => $this->product->server_configs['package'],
            'ip' => $ip,
            'notify' => 'yes'
        ]);

        $result = $sock->fetch_parsed_body();

        if ($result['error'] != "0") {
            return ['code' => $result['error'], 'msg' => $result['text'] . '<br>' . $result['details']];
        } else {
            return ['code' => 0];
        }
    }

    private function api_select_users($type)
    {
        $sock = $this->createSocket();

        if ($this->product->type == 'reseller') {
            $location = 'CMD_RESELLER_SHOW';
        } else {
            $location = 'CMD_SELECT_USERS';
        }

        switch ($type) {
            case 'suspend':
                $data = [
                    'location' => $location,
                    'suspend' => 'Suspend',
                    'select0' => $this->service->username
                ];
                break;
            case 'unsuspend':
                $data = [
                    'location' => $location,
                    'suspend' => 'Unsuspend',
                    'select0' => $this->service->username
                ];
                break;
            case 'delete':
                $data = [
                    'confirmed' => 'Confirm',
                    'delete' => 'yes',
                    'select0' => $this->service->username
                ];
                break;
        }

        $sock->query('/CMD_API_SELECT_USERS', $data);

        $result = $sock->fetch_parsed_body();

        if ($result['error'] != "0") {
            return ['code' => $result['error'], 'msg' => $result['text'] . '<br>' . $result['details']];
        } else {
            return ['code' => 0];
        }
    }

    /**
     * @inheritDoc
     */
    public function suspend()
    {
        return $this->api_select_users('suspend');
    }

    /**
     * @inheritDoc
     */
    public function unsuspend()
    {
        return $this->api_select_users('unsuspend');
    }

    /**
     * @inheritDoc
     */
    public function terminate()
    {
        return $this->api_select_users('delete');
    }

    /**
     * @inheritDoc
     */
    public function changePassword($password)
    {
        $sock = $this->createSocket();

        $sock->query('/CMD_API_USER_PASSWD', [
            'username' => $this->service->username,
            'passwd' => $password,
            'passwd2' => $password
        ]);

        $result = $sock->fetch_parsed_body();

        if ($result['error'] != "0") {
            return ['code' => $result['error'], 'msg' => $result['text'] . '<br>' . $result['details']];
        } else {
            return ['code' => 0];
        }
    }

    /**
     * @inheritDoc
     */
    public function upgradeDowngrade()
    {
        // TODO: Implement upgradeDowngrade() method.
    }
}