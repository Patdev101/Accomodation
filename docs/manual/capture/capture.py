"""Walks through the demo copy of the system (port 8091) and saves the manual's screenshots."""

import subprocess
import sys
from datetime import date, timedelta
from pathlib import Path

from playwright.sync_api import sync_playwright

BASE = 'http://127.0.0.1:8091'
OUT = Path(r'C:\Users\Temp\Documents\GitHub\Accomodation\docs\manual\screens')
PROJECT = Path(r'C:\Users\Temp\Documents\GitHub\Accomodation')
DEMO = Path(__file__).parent
TODAY = date.today()


def artisan_php(code):
    """Run PHP inside the demo copy (SQLite) and return what it prints."""
    script = DEMO / 'step.php'
    script.write_text("<?php\nif (config('database.default') !== 'sqlite') { throw new Exception('wrong database'); }\n" + code, encoding='utf-8')
    env = {**__import__('os').environ, 'DB_CONNECTION': 'sqlite', 'DB_DATABASE': str(DEMO / 'demo.sqlite'), 'DB_URL': '', 'APP_URL': BASE}
    result = subprocess.run(['php', 'artisan', 'tinker', str(script)], cwd=PROJECT, env=env, capture_output=True, text=True)
    lines = [line.strip().lstrip('\ufeff') for line in result.stdout.splitlines() if line.strip().lstrip('\ufeff')]
    return lines[-1] if lines else ''


def run(p):
    browser = p.chromium.launch(channel='chrome')
    context = browser.new_context(viewport={'width': 1600, 'height': 880}, device_scale_factor=1.2)
    page = context.new_page()
    page.set_default_timeout(20000)

    def go(path):
        page.goto(BASE + path)
        page.wait_for_load_state('load')

    def shot(name, top=True):
        if top:
            page.evaluate('window.scrollTo(0, 0)')
        page.wait_for_timeout(350)
        page.screenshot(path=str(OUT / f'{name}.png'))
        print('saved', name)

    def scroll_to(text, offset=90):
        # scroll a section heading to the top of the window (minus a small margin)
        page.evaluate('''([text, offset]) => {
            const h = Array.from(document.querySelectorAll('h3')).find(e => e.textContent.trim().startsWith(text));
            window.scrollTo(0, h.getBoundingClientRect().top + window.scrollY - offset);
        }''', [text, offset])

    def login(email):
        context.clear_cookies()
        go('/login')
        page.fill('#email', email)
        page.fill('#password', 'password')
        page.click('button[type=submit]')
        page.wait_for_load_state('load')

    def option_value(select, text):
        return page.evaluate(
            """([select, text]) => Array.from(document.querySelector(select).options).find(o => o.textContent.includes(text)).value""",
            [select, text],
        )

    # ---------------- signed out ----------------
    context.clear_cookies()
    go('/login')
    page.fill('#email', 'reception@example.com')
    shot('01-login')
    go('/forgot-password')
    shot('02-forgot-password')
    token = artisan_php("echo Password::createToken(App\\Models\\User::where('email', 'ana.reyes@example.com')->first());")
    go(f'/reset-password/{token}?email=ana.reyes%40example.com')
    shot('03-set-password')

    # ---------------- reception: reservations ----------------
    login('reception@example.com')

    def reserve(name, company, contact, room_text, guests, start, end, open_shot=None):
        go('/bookings')
        page.click('button:has-text("+ New reservation")')
        page.fill('#res_guest_name', name)
        page.fill('input[name=company]', company)
        page.fill('input[name=contact_no]', contact)
        page.select_option('#res_room_id', option_value('#res_room_id', room_text))
        page.fill('input[name=no_of_guests]', str(guests))
        page.fill('#res_check_in', start)
        page.fill('#res_check_out', end)
        if open_shot:
            shot(open_shot)
        page.click('button:has-text("Save reservation")')
        page.wait_for_load_state('load')

    reserve('Maria Santos', 'ABC Trading', '0918 123 4567', 'Villa 1 —', 2,
            f'{TODAY}T14:00', f'{TODAY + timedelta(days=2)}T12:00')
    reserve('Jose Cruz', 'Pacific Steel Works', '0917 555 0101', 'Villa 2 —', 3,
            f'{TODAY + timedelta(days=3)}T15:00', f'{TODAY + timedelta(days=5)}T12:00', open_shot='29-new-reservation')
    reserve('Carlo Reyes', 'Mindanao Cables', '0920 777 1234', 'B-104 —', 2,
            f'{TODAY + timedelta(days=6)}T13:00', f'{TODAY + timedelta(days=8)}T12:00')

    # ---------------- reception: check-ins ----------------
    def check_in(name, company, contact, address, room, rate='nightly', others=(), shots=False, wrong_rate=None):
        go('/checkin')
        page.fill('#guest_name', name)
        page.fill('#company', company)
        page.fill('#contact_no', contact)
        page.fill('#address', address)
        page.fill('#no_of_guests', str(1 + len(others)))
        for i, (o_name, o_address, o_contact) in enumerate(others):
            page.fill(f'input[name="guest_list[{i}][name]"]', o_name)
            page.fill(f'input[name="guest_list[{i}][address]"]', o_address)
            page.fill(f'input[name="guest_list[{i}][contact_no]"]', o_contact)
        page.fill('#remarks', 'Plant maintenance contractor' if company else 'Visitor')
        if shots:
            shot('15-checkin-form')
            page.locator('#guestListBox').scroll_into_view_if_needed()
            page.evaluate('window.scrollBy(0, 160)')
            shot('16-checkin-form-lower', top=False)
        page.click('#stepNext')
        page.check('input[name=verified]')
        if shots:
            shot('17-checkin-verification')
        page.click('#stepNext')
        page.select_option('#id_type', label='Company ID')
        page.fill('#id_number', 'MMMC-' + str(abs(hash(name)) % 90000 + 10000))
        page.check('input[name=id_surrendered]')
        if shots:
            shot('18-checkin-id')
        page.click('#stepNext')
        room_value = page.locator(f'#room_id option[data-name="{room}"]').get_attribute('value')
        page.select_option('#billing_rate_type', rate)
        page.select_option('#room_id', room_value)
        if wrong_rate:
            # the room stays chosen and the page lists the rates it does have
            page.select_option('#billing_rate_type', wrong_rate)
            shot('34-checkin-rate-message')
            page.select_option('#billing_rate_type', rate)
        if shots:
            shot('19-checkin-room')
        page.click('#stepFinish')
        page.wait_for_load_state('load')

    check_in('Juan Dela Cruz', 'Sample Corp', '0917 123 4567', 'Brgy. Poblacion, Calapan City', 'B-101',
             others=[('Mark Villanueva', 'Brgy. Lalud, Calapan City', '0918 222 3344')], shots=True)
    page.evaluate('window.scrollTo(0, 120)')
    shot('20-room-slip', top=False)

    check_in('Pedro Lim', 'Island Power Co.', '0919 444 5566', 'Naujan, Oriental Mindoro', 'B-105', rate='daytour', wrong_rate='nightly')
    check_in('Ramon Torres', 'Delta Engineering', '0921 888 9900', 'Pinamalayan, Oriental Mindoro', 'B-106')
    check_in('Liza Garcia', '', '0927 333 1122', 'Puerto Galera, Oriental Mindoro', 'Villa 4', rate='daytour',
             others=[('Nina Garcia', 'Puerto Galera, Oriental Mindoro', '0927 333 1123')])

    # Pedro was expected yesterday: makes the overdue alerts appear
    pedro = artisan_php("echo App\\Models\\Booking::where('guest_name', 'Pedro Lim')->update(['check_out' => now()->subDay()->toDateString(), 'check_out_time' => '12:00']);")

    # ---------------- reception: full check-out of Ramon Torres ----------------
    go('/checkout')
    page.locator('tr:has-text("Ramon Torres") a:has-text("Check out")').click()
    page.wait_for_load_state('load')
    ramon = page.url.rstrip('/').split('/')[-1]
    print('pedro update:', pedro, '| ramon id:', ramon)
    shot('22-checkout-verification')
    page.click('button:has-text("Confirm and continue")')
    page.wait_for_load_state('load')
    page.fill('#inspection_notes', 'Room in good condition, key returned')
    page.fill('#damage_notes', 'None')
    page.click('#addChargeLine')
    page.locator('input[name$="[description]"]').last.fill('Laundry service')
    page.locator('input[name$="[amount]"]').last.fill('150')
    scroll_to('Room inspection and additional charges', 40)
    shot('23-checkout-inspection', top=False)
    page.click('button:has-text("Save inspection and charges")')
    page.wait_for_load_state('load')
    scroll_to('Billing and payment', 40)
    shot('24-checkout-billing', top=False)
    page.click('button:has-text("Record payment")')
    page.wait_for_timeout(300)
    shot('35-confirm-popup', top=False)
    page.click('#confirmOk')
    page.wait_for_load_state('load')
    scroll_to('Return surrendered ID', 180)
    shot('25-checkout-return-id', top=False)
    page.click('button:has-text("ID returned to guest")')
    page.wait_for_load_state('load')
    page.fill('#checkout_notes', 'Guest left the facility, ID returned')
    scroll_to('Record check-out in guest log', 260)
    shot('26-checkout-record', top=False)
    page.click('button:has-text("Record check-out")')
    page.wait_for_timeout(300)
    page.click('#confirmOk')
    page.wait_for_load_state('load')
    go(f'/billing/{ramon}')
    shot('27-guest-bill')

    # ---------------- reception: lists ----------------
    go('/dashboard')
    shot('12-reception-dashboard')
    page.locator('#rooms').scroll_into_view_if_needed()
    page.evaluate('window.scrollBy(0, -20)')
    shot('13-dashboard-rooms', top=False)
    scroll_to('Guests to arrive', 30)
    shot('14-dashboard-lists', top=False)

    go('/checkout')
    shot('21-checkout-list')
    go('/bookings')
    shot('28-reservations')
    page.locator('tr:has-text("Maria Santos") button:has-text("View")').click()
    page.wait_for_timeout(300)
    shot('31-reservation-details', top=False)
    go('/bookings')
    page.locator('tr:has-text("Maria Santos") a:has-text("Estimate")').click()
    page.wait_for_load_state('load')
    shot('30-estimated-bill')
    go('/calendar')
    page.evaluate('window.scrollTo(0, 140)')
    shot('32-calendar', top=False)
    go('/reports')
    shot('33-guest-log')

    # ---------------- admin ----------------
    login('admin@example.com')
    go('/admin')
    shot('04-admin-dashboard')
    scroll_to('Locations and their rooms', 30)
    shot('05-admin-dashboard-lower', top=False)
    go('/admin/locations')
    shot('11-locations')
    go('/rooms')
    shot('07-rooms')
    go('/rooms/create')
    page.fill('#room_no', 'B-107')
    page.select_option('#location_id', label='Barracks')
    page.fill('#capacity', '4')
    page.fill('#rate', '400')
    page.fill('#rate_daytour', '300')
    for item in ('Bed space', 'Electric fan', 'Locker'):
        page.fill('#inclusion_input', item)
        page.press('#inclusion_input', 'Enter')
    shot('08-add-room')
    page.evaluate('window.scrollTo(0, document.body.scrollHeight)')
    shot('09-add-room-inclusions', top=False)
    go('/rooms')
    page.locator('tr:has-text("Villa 1") a:has-text("Edit")').first.click()
    page.wait_for_load_state('load')
    shot('10-edit-room')

    # ---------------- super admin ----------------
    login('superadmin@example.com')
    go('/admin')
    shot('36-superadmin-dashboard')
    go('/admin/users')
    shot('06-accounts')

    browser.close()


with sync_playwright() as p:
    run(p)
print('done')
