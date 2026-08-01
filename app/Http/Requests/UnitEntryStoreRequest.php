<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnitEntryStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        if ($this->has('inputs') && is_array($this->inputs)) {
            $inputs = $this->inputs;
            foreach ($inputs as $key => $data) {
                // Bersihkan format angka (hapus titik ribuan, ganti koma jadi titik desimal)
                if (isset($data['unit_entry'])) {
                    $val = str_replace('.', '', $data['unit_entry']);
                    $val = str_replace(',', '.', $val);
                    $inputs[$key]['unit_entry'] = $val === '' ? null : $val;
                }
                if (isset($data['rp_unit_entry'])) {
                    $val = str_replace('.', '', $data['rp_unit_entry']);
                    $val = str_replace(',', '.', $val);
                    $inputs[$key]['rp_unit_entry'] = $val === '' ? null : $val;
                }
            }
            $this->merge(['inputs' => $inputs]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'month' => ['required', 'string', 'digits:2'],
            'year' => ['required', 'string', 'digits:4'],
            'inputs' => ['required', 'array'],
            'inputs.*.unit_entry' => ['nullable', 'numeric', 'required_with:inputs.*.rp_unit_entry'],
            'inputs.*.rp_unit_entry' => ['nullable', 'numeric', 'required_with:inputs.*.unit_entry'],
        ];
    }

    public function messages(): array
    {
        return [
            'month.required' => 'Bulan wajib diisi.',
            'month.digits' => 'Bulan harus berupa angka 2 digit.',
            'year.required' => 'Tahun wajib diisi.',
            'year.digits' => 'Tahun harus berupa angka 4 digit.',
            'inputs.required' => 'Input data wajib diisi.',
            'inputs.array' => 'Input data harus berupa array.',
            'inputs.*.unit_entry.numeric' => 'Unit Entry harus berupa angka.',
            'inputs.*.rp_unit_entry.numeric' => 'RP/Unit Entry harus berupa angka.',
            'inputs.*.unit_entry.required_with' => 'Unit Entry wajib diisi jika RP/UE terisi.',
            'inputs.*.rp_unit_entry.required_with' => 'RP/UE wajib diisi jika Unit Entry terisi.',
        ];
    }
}
