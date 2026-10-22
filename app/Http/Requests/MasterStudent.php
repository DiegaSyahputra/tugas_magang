<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class MasterStudent extends FormRequest
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

        $id = $id ?? $this->route('student');

        $userId = null;

        if ($id) {
            $student = Student::find($id);

            if ($student) {
                 $userId = $student->user_id;
            }
        }

        return [
            'name'               => ['required', 'string', 'max:255'],
            'user_id'            => ['nullable', 'exists:users,id'],
            'student_code'       => [
                'string',
                'max:50',
                Rule::unique('students', 'student_code')->ignore($id)
            ],
            'email'              => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($userId)],
            'date_birth'         => ['required', 'date'],
            'tel'                => ['required', 'string', 'max:15', 'regex:/^[0-9+\-\s]+$/'],
            'status'             => ['required', Rule::in(['regular', 'irregular'])],
        ];
    }

    public function messages(){
        return [
            'name.required'               => 'Nama siswa tidak boleh kosong.',
            'name.max'                    => 'Nama siswa maksimal 255 karakter.',

            'user_id.exists'              => 'Pengguna (user) yang dipilih tidak valid.',

            'student_code.unique'         => 'Kode/NIM siswa sudah terdaftar.',

            'email.required'              => 'Email tidak boleh kosong',
            'email.max'                   => 'Email maksimal 100 karakter',
            'email.unique'                => 'Email sudah digunakan',

            'date_birth.required'         => 'Tanggal lahir wajib diisi.',
            'date_birth.date'             => 'Format tanggal lahir tidak valid.',

            'tel.required'                => 'Nomor telepon wajib diisi.',
            'tel.max'                     => 'Nomor telepon maksimal 15 karakter.',
            'tel.regex'                   => 'Format nomor telepon tidak valid.',

            'status.required'             => 'Silakan pilih status siswa.',
            'status.in'                   => 'Pilihan status tidak valid.',
        ];
    }
}
