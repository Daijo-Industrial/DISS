<?php

namespace Tests\Feature\Ticketing;

use App\Livewire\Ticketing\SupportBubble;
use Livewire\Livewire;
use Tests\TestCase;

class SupportBubbleTest extends TestCase
{
    public function test_support_bubble_renders_toggle_button_and_initial_state()
    {
        Livewire::test(SupportBubble::class)
            ->assertSet('isOpen', false)
            ->assertSeeHtml('@click="toggle()"')
            ->assertSeeHtml('@click.outside="close()"')
            ->assertSeeHtml('aria-label="Toggle IT Concierge"')
            ->assertSeeHtml('x-show="open"')
            ->assertStatus(200);
    }

    public function test_support_bubble_toggle_method_acts_like_a_toggle()
    {
        Livewire::test(SupportBubble::class)
            ->assertSet('isOpen', false)
            ->call('toggle')
            ->assertSet('isOpen', true)
            ->call('toggle')
            ->assertSet('isOpen', false);
    }

    public function test_support_bubble_handles_open_event_and_switches_tab()
    {
        Livewire::test(SupportBubble::class)
            ->assertSet('isOpen', false)
            ->dispatch('open-support-bubble', ['tab' => 'my_tickets'])
            ->assertSet('isOpen', true)
            ->assertSet('activeTab', 'my_tickets');
    }

    public function test_support_bubble_clears_unread_updates_when_opened()
    {
        $component = Livewire::test(SupportBubble::class);
        $component->set('hasUnreadUpdates', true);
        $component->set('isOpen', true);
        $component->assertSet('hasUnreadUpdates', false);
    }

    public function test_support_bubble_renders_simplified_request_types_and_description()
    {
        Livewire::test(SupportBubble::class)
            ->set('isOpen', true)
            ->assertSet('issue_type', 'Software / Application Issue')
            ->assertSee(__('ticketing.login_help.options.password_reset'))
            ->assertSee(__('ticketing.login_help.options.software'))
            ->assertSee(__('ticketing.login_help.options.hardware'))
            ->assertSee(__('ticketing.login_help.options.network'))
            ->assertSee(__('ticketing.concierge.form.custom_title'))
            ->assertSee(__('ticketing.concierge.form.details'))
            ->assertDontSee(__('ticketing.concierge.form.page_url'))
            ->assertStatus(200);
    }

    public function test_support_bubble_switches_request_type_properly()
    {
        Livewire::test(SupportBubble::class)
            ->set('isOpen', true)
            ->set('issue_type', 'Hardware / Computer / Printer')
            ->assertSet('issue_type', 'Hardware / Computer / Printer')
            ->set('request_type', 'feature')
            ->assertSet('request_type', 'feature')
            ->set('request_type', 'other')
            ->assertSet('request_type', 'other');
    }
}
