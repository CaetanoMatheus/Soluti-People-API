<?php

namespace App\Services;

use App\User;
use Illuminate\Support\Facades\DB;

class UserService extends Service
{

    private $telephoneService;
    private $addressService;

    public function __construct(TelephoneService $telephoneService, AddressService $addressService)
    {
        $this->telephoneService = $telephoneService;
        $this->addressService = $addressService;
    }

    public function update(array $data, int $id)
    {
        $telephones = [];
        DB::beginTransaction();
        $user = User::find($id);
        if (is_null($user)) {
            return null;
        }
        $user->fill($data);
        $user->save();
        if (isset($data['telephones'])) {
            $telephones = $this->manageTelephones($data['telephones'], $user);
        }
        if (isset($data['address'])) {
            $this->manageAddress($data['address'], $user);
        }
        DB::commit();
        $user->telephones = $telephones;
        return $user;
    }

    private function manageTelephones(array $telephones, User $user)
    {
        $phones = [];
        if (empty($telephones)) {
            foreach ($user->telephones as $telephone) {
                $this->telephoneService->destroy($telephone->id);
            }
            return $phones;
        }
        foreach ($telephones as $telephone) {
            if (isset($telephone['id'])) {
                $phones[] = $this->telephoneService->update($telephone, $telephone['id']);
            } else {
                $telephone['user_id'] = $user->id;
                $phones[] = $this->telephoneService->create($telephone);
            }
        }
        return $phones;
    }

    private function manageAddress(array $address, User $user)
    {
        if (empty($address)) {
            if (!is_null($user->address)) {
                return $this->addressService->destroy($user->address->id);
            }
        } else if (isset($address['id'])) {
            return $this->addressService->update($address, $user->address->id);
        } else {
            $address['user_id'] = $user->id;
            return $this->addressService->create($address);
        }
    }
}
