<?php

namespace Tests\Feature;

use App\Livewire\Components\SearchableDropdown;
use App\Livewire\InspectionForm\StepHeader;
use App\Models\MasterDataPart;
use App\Models\MasterDataRogCustomerName;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class InspectionFormStepHeaderValidationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_validation_required_message_is_human_readable_in_indonesian(): void
    {
        app()->setLocale('id');

        $validator = validator(['customer' => ''], ['customer' => 'required']);
        $this->assertTrue($validator->fails());

        $errorMessage = $validator->errors()->first('customer');
        $this->assertNotSame('validation.required', $errorMessage);
        $this->assertStringContainsString('wajib diisi', $errorMessage);
    }

    public function test_validation_required_message_is_human_readable_in_english(): void
    {
        app()->setLocale('en');

        $validator = validator(['customer' => ''], ['customer' => 'required']);
        $this->assertTrue($validator->fails());

        $errorMessage = $validator->errors()->first('customer');
        $this->assertNotSame('validation.required', $errorMessage);
        $this->assertSame('The Customer field is required.', $errorMessage);
    }

    public function test_step_header_validation_errors_show_human_readable_messages(): void
    {
        app()->setLocale('id');

        Livewire::test(StepHeader::class)
            ->call('saveStep')
            ->assertHasErrors([
                'customer' => 'required',
                'part_number' => 'required',
                'part_name' => 'required',
                'weight' => 'required',
                'weight_uom' => 'required',
                'material' => 'required',
                'color' => 'required',
                'tool_number_or_cav_number' => 'required',
                'machine_number' => 'required',
                'shift' => 'required',
                'operator' => 'required',
                'inspector' => 'required',
            ])
            ->assertSee('Customer wajib diisi.')
            ->assertDontSee('validation.required');
    }

    public function test_searchable_dropdown_select_dispatches_and_updates_step_header(): void
    {
        $customer = new MasterDataRogCustomerName();
        $customer->name = 'PT Test Customer';
        $customer->save();

        Livewire::test(SearchableDropdown::class, [
            'name' => 'customer',
            'model' => MasterDataRogCustomerName::class,
            'column' => 'name',
        ])
            ->call('select', 'PT Test Customer')
            ->assertDispatched('dropdownSelected', function ($event, $params) {
                $payload = $params[0] ?? $params;
                return ($payload['field'] ?? null) === 'customer'
                    && ($payload['value'] ?? null) === 'PT Test Customer';
            });

        Livewire::test(StepHeader::class)
            ->dispatch('dropdownSelected', ['field' => 'customer', 'value' => 'PT Test Customer'])
            ->assertSet('customer', 'PT Test Customer');
    }

    public function test_searchable_dropdown_custom_value_fallback(): void
    {
        Livewire::test(SearchableDropdown::class, [
            'name' => 'customer',
            'model' => MasterDataRogCustomerName::class,
            'column' => 'name',
        ])
            ->call('selectCustom', 'PT Custom Customer')
            ->assertSet('value', 'PT Custom Customer')
            ->assertDispatched('dropdownSelected', function ($event, $params) {
                $payload = $params[0] ?? $params;
                return ($payload['field'] ?? null) === 'customer'
                    && ($payload['value'] ?? null) === 'PT Custom Customer';
            });
    }
}
