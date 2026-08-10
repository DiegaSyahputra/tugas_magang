<?php

namespace App\Http\Requests;

use App\Models\Teacher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class MasterTeacher extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules($id = null): array
    {

        $id = $id ?? $this->route('teacher');

        $userId = null;

        if ($id) {
            $teacher = Teacher::find($id);

            if ($teacher) {
                $userId = $teacher->user_id;
            }
        }

        return [
            'name'           => ['required', 'string', 'max:100'],
            'user_id'        => ['nullable', 'exists:users,id'],
            'email'          => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($userId)],
            'specialization' => ['required', 'string', 'max:150'],
            'tel'            => ['required', 'string', 'max:15', 'regex:/^[0-9+\-\s]+$/'],
        ];
    }

    public function messages(){
        return [
            'name.required'           => 'Nama guru tidak boleh kosong.',
            'name.max'                => 'Nama guru maksimal 100 karakter.',

            'user_id.exists'          => 'Pengguna (user) yang dipilih tidak ditemukan.',

            'email.required'          => 'Email tidak boleh kosong.',
            'email.email'             => 'Format email tidak valid.',
            'email.max'               => 'Email maksimal 100 karakter.',
            'email.unique'            => 'Email sudah digunakan.',

            'specialization.required' => 'Spesialisasi/bidang studi wajib diisi.',
            'specialization.max'      => 'Spesialisasi maksimal 150 karakter.',

            'tel.required'            => 'Nomor telepon wajib diisi.',
            'tel.max'                 => 'Nomor telepon maksimal 15 karakter.',
            'tel.regex'               => 'Format nomor telepon tidak valid.',
        ];
    }
}
