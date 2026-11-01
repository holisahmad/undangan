<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Contact\PhoneNumber;
use Tests\TestCase;

/**
 * A number typed the local way ("0813...") must become a link WhatsApp accepts.
 * Sending the local form produces https://wa.me/081391515363, which opens an
 * error page instead of a chat.
 */
final class PhoneNumberTest extends TestCase
{
    public function test_a_local_number_gains_the_country_code(): void
    {
        $this->assertSame('6281391515363', PhoneNumber::international('081391515363'));
        $this->assertSame('+6281391515363', PhoneNumber::e164('081391515363'));
        $this->assertSame('https://wa.me/6281391515363', PhoneNumber::whatsappLink('081391515363'));
    }

    public function test_the_country_code_is_not_added_twice(): void
    {
        $this->assertSame('https://wa.me/6281234567890', PhoneNumber::whatsappLink('+62 812-3456-7890'));
        $this->assertSame('https://wa.me/6281234567890', PhoneNumber::whatsappLink('6281234567890'));
        $this->assertSame('https://wa.me/6281234567890', PhoneNumber::whatsappLink('812 3456 7890'));
    }

    public function test_empty_and_implausible_numbers_produce_no_link(): void
    {
        $this->assertNull(PhoneNumber::whatsappLink(''));
        $this->assertNull(PhoneNumber::whatsappLink('   '));
        $this->assertNull(PhoneNumber::whatsappLink('12345'));
        $this->assertNull(PhoneNumber::e164(''));
    }

    public function test_the_number_is_printed_in_the_local_grouping(): void
    {
        $this->assertSame('0813-9151-5363', PhoneNumber::display('081391515363'));
        $this->assertSame('0813-9151-5363', PhoneNumber::display('+62 813-9151-5363'));
        $this->assertSame('0812-3456-7890', PhoneNumber::display('0812 3456 7890'));
    }
}
