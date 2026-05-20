<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:100'],
            'hostname'        => ['required', 'string', 'max:255'],
            'ip_address'      => ['nullable', 'ip'],
            'ssh_port'        => ['nullable', 'integer', 'min:1', 'max:65535'],
            'ssh_user'        => ['nullable', 'string', 'max:100'],
            'ssh_auth_method' => ['nullable', 'in:key,password'],
            'ssh_private_key' => ['nullable', 'string'],
            'ssh_password'    => ['nullable', 'string', 'max:255'],
            'os'              => ['nullable', 'string', 'max:100'],
            'tags'            => ['nullable', 'array'],
            'tags.*'          => ['string', 'max:50'],
            'notes'           => ['nullable', 'string', 'max:2000'],
        ];
    }
}
