"""Builds docs/Guest-Accommodation-User-Manual.pdf from the text below and the pictures in screens/.

Run:  python docs\\manual\\build_manual.py     (needs: pip install reportlab)
To update a picture, replace the PNG in screens/ (keep the file name) and run again.
"""

from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib.units import mm
from reportlab.lib.utils import ImageReader
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    BaseDocTemplate, Frame, Image, KeepTogether, ListFlowable, ListItem, PageBreak,
    PageTemplate, Paragraph, Spacer, Table, TableStyle,
)
from reportlab.platypus.tableofcontents import TableOfContents

HERE = Path(__file__).parent
SCREENS = HERE / 'screens'
OUTPUT = HERE.parent / 'Guest-Accommodation-User-Manual.pdf'

TITLE = 'Guest Accommodation System'
SUBTITLE = 'User Manual'
COMPANY = 'Mindoro Marine Manufacturing Corporation'
VERSION = 'October 2026'

NAVY = colors.HexColor('#1f3c73')
TEAL = colors.HexColor('#1b9bb3')
TEXT = colors.HexColor('#1f2a3a')
MUTED = colors.HexColor('#6b7686')
BORDER = colors.HexColor('#d9e0e8')
LIGHT = colors.HexColor('#f3f6fa')
NOTE_BG = colors.HexColor('#eef8fb')
NOTE_BORDER = colors.HexColor('#c5e6ee')
WARN_BG = colors.HexColor('#fdf0d5')
WARN_BORDER = colors.HexColor('#efdca8')

# Segoe UI has the peso sign and the arrows used in the text
FONTS = Path('C:/Windows/Fonts')
pdfmetrics.registerFont(TTFont('Body', str(FONTS / 'segoeui.ttf')))
pdfmetrics.registerFont(TTFont('Body-Bold', str(FONTS / 'segoeuib.ttf')))
pdfmetrics.registerFont(TTFont('Body-Italic', str(FONTS / 'segoeuii.ttf')))
pdfmetrics.registerFontFamily('Body', normal='Body', bold='Body-Bold', italic='Body-Italic', boldItalic='Body-Bold')

MARGIN = 18 * mm
WIDTH = A4[0] - 2 * MARGIN

S = {
    'body': ParagraphStyle('body', fontName='Body', fontSize=10.5, leading=15, textColor=TEXT, spaceAfter=6),
    'h1': ParagraphStyle('h1', fontName='Body-Bold', fontSize=20, leading=25, textColor=NAVY, spaceAfter=4),
    'h2': ParagraphStyle('h2', fontName='Body-Bold', fontSize=13.5, leading=18, textColor=NAVY, spaceBefore=12, spaceAfter=5, keepWithNext=True),
    'h3': ParagraphStyle('h3', fontName='Body-Bold', fontSize=11, leading=15, textColor=TEXT, spaceBefore=8, spaceAfter=3, keepWithNext=True),
    'part': ParagraphStyle('part', fontName='Body-Bold', fontSize=10, leading=13, textColor=TEAL, spaceAfter=2),
    'lead': ParagraphStyle('lead', fontName='Body', fontSize=11, leading=16, textColor=MUTED, spaceAfter=10),
    'item': ParagraphStyle('item', fontName='Body', fontSize=10.5, leading=15, textColor=TEXT),
    'caption': ParagraphStyle('caption', fontName='Body-Italic', fontSize=9, leading=12, textColor=MUTED, alignment=TA_CENTER, spaceBefore=4, spaceAfter=10),
    'box': ParagraphStyle('box', fontName='Body', fontSize=10, leading=14.5, textColor=TEXT),
    'cell': ParagraphStyle('cell', fontName='Body', fontSize=9.5, leading=13, textColor=TEXT),
    'head': ParagraphStyle('head', fontName='Body-Bold', fontSize=9.5, leading=13, textColor=NAVY),
    'contents': ParagraphStyle('contents', fontName='Body-Bold', fontSize=20, leading=25, textColor=NAVY, spaceAfter=4),
    'toc1': ParagraphStyle('toc1', fontName='Body-Bold', fontSize=11, leading=20, textColor=NAVY),
    'toc2': ParagraphStyle('toc2', fontName='Body', fontSize=10, leading=16, textColor=TEXT, leftIndent=14),
}

story = []


def part(text):
    """Start a chapter on a new page. `text` is 'Part label|Chapter title'."""
    label, title = text.split('|')
    story.append(PageBreak())
    story.append(Paragraph(label.upper(), S['part']))
    story.append(Paragraph(title, S['h1']))
    story.append(Table([['']], colWidths=[WIDTH], rowHeights=[2.2], style=[('BACKGROUND', (0, 0), (-1, -1), TEAL)]))
    story.append(Spacer(1, 8))


def h2(text):
    story.append(Paragraph(text, S['h2']))


def h3(text):
    story.append(Paragraph(text, S['h3']))


def p(text, style='body'):
    story.append(Paragraph(text, S[style]))


def bullets(items):
    story.append(ListFlowable(
        [ListItem(Paragraph(i, S['item']), spaceAfter=3) for i in items],
        bulletType='bullet', bulletFontName='Body', bulletFontSize=10, bulletColor=TEAL, leftIndent=16, bulletOffsetY=-1,
    ))
    story.append(Spacer(1, 6))


def steps(items):
    # a numbered list is never split over two pages
    story.append(KeepTogether([ListFlowable(
        [ListItem(Paragraph(i, S['item']), spaceAfter=4) for i in items],
        bulletType='1', bulletFormat='%s.', bulletFontName='Body-Bold', bulletFontSize=10.5, bulletColor=NAVY, leftIndent=20,
    ), Spacer(1, 6)]))


def box(text, kind='note'):
    """A tinted box. kind = 'note' (good to know) or 'warn' (be careful)."""
    background, border, label = (NOTE_BG, NOTE_BORDER, 'Good to know') if kind == 'note' else (WARN_BG, WARN_BORDER, 'Be careful')
    table = Table([[Paragraph(f'<b>{label}:</b> {text}', S['box'])]], colWidths=[WIDTH])
    table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), background), ('BOX', (0, 0), (-1, -1), 0.8, border),
        ('LEFTPADDING', (0, 0), (-1, -1), 10), ('RIGHTPADDING', (0, 0), (-1, -1), 10),
        ('TOPPADDING', (0, 0), (-1, -1), 7), ('BOTTOMPADDING', (0, 0), (-1, -1), 8),
    ]))
    story.append(KeepTogether([table, Spacer(1, 8)]))


def shot(name, caption, width=WIDTH * 0.88):
    """A screenshot from screens/ with a thin border and a caption underneath."""
    path = SCREENS / f'{name}.png'
    pixel_width, pixel_height = ImageReader(str(path)).getSize()
    picture = Image(str(path), width=width - 2, height=(width - 2) * pixel_height / pixel_width)
    frame = Table([[picture]], colWidths=[width])
    frame.setStyle(TableStyle([
        ('BOX', (0, 0), (-1, -1), 0.8, BORDER), ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
        ('LEFTPADDING', (0, 0), (-1, -1), 0), ('RIGHTPADDING', (0, 0), (-1, -1), 0),
        ('TOPPADDING', (0, 0), (-1, -1), 1), ('BOTTOMPADDING', (0, 0), (-1, -1), 1),
    ]))
    frame.hAlign = 'CENTER'
    story.append(KeepTogether([Spacer(1, 4), frame, Paragraph(caption, S['caption'])]))


def table(header, rows, widths):
    data = [[Paragraph(c, S['head']) for c in header]] + [[Paragraph(c, S['cell']) for c in row] for row in rows]
    t = Table(data, colWidths=[WIDTH * w for w in widths], repeatRows=1)
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#eef2f7')), ('GRID', (0, 0), (-1, -1), 0.6, BORDER),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'), ('LEFTPADDING', (0, 0), (-1, -1), 7), ('RIGHTPADDING', (0, 0), (-1, -1), 7),
        ('TOPPADDING', (0, 0), (-1, -1), 5), ('BOTTOMPADDING', (0, 0), (-1, -1), 6),
    ]))
    story.append(t)
    story.append(Spacer(1, 10))


# =====================================================================
# CONTENTS PAGE
# =====================================================================

story.append(PageBreak())
story.append(Paragraph('Contents', S['contents']))
story.append(Table([['']], colWidths=[WIDTH], rowHeights=[2.2], style=[('BACKGROUND', (0, 0), (-1, -1), TEAL)]))
story.append(Spacer(1, 10))
contents = TableOfContents()
contents.levelStyles = [S['toc1'], S['toc2']]
contents.dotsMinLevel = 0
story.append(contents)

# =====================================================================
# 1. ABOUT THE SYSTEM
# =====================================================================

part('Getting started|1. About the system')
p('The Guest Accommodation System is where the company records who is staying in the Guest Villa and the Barracks: '
  'advance reservations, check-in, check-out, the bill, and the guest log. It follows the company\'s printed '
  'check-in and check-out process step by step.', 'lead')

h2('Who uses it')
p('There are two kinds of account. Each one sees only its own screens.')
table(['Account', 'What it is for', 'Screens'], [
    ['<b>Admin</b>', 'Sets the system up: the locations, the rooms and their rates, and the staff accounts.',
     'Admin Dashboard, Locations, Rooms, Accounts'],
    ['<b>Reception</b>', 'Runs the front desk every day: reservations, check-in, check-out, payments and the guest log.',
     'Dashboard, Check-in, Check-out, Reservations, Calendar, Guest Log'],
], [0.18, 0.47, 0.35])

h2('How a stay moves through the system')
steps([
    'The <b>admin</b> adds a location (for example Barracks), then adds its rooms with their rates.',
    'A guest may book ahead. Reception records this on the <b>Reservations</b> page. This step is optional: a walk-in guest skips it.',
    'When the guest arrives, reception does the <b>Check-in</b>. The room becomes <b>Occupied</b> and a room slip is printed.',
    'When the guest leaves, reception does the <b>Check-out</b>: verification, room inspection, payment, returning the ID, and recording it.',
    'The stay is kept in the <b>Guest Log</b>. The room is cleaned and reception marks it <b>Available</b> again.',
])

h2('What the room statuses mean')
p('Every room has one status. It changes by itself as you follow the steps; reception only sets the last one by hand.')
table(['Status', 'Meaning', 'How it changes'], [
    ['<b>Available</b>', 'Ready for a guest.', 'Set by reception on the Dashboard when the room is ready.'],
    ['<b>Occupied</b>', 'A guest is staying in it.', 'Set automatically when a check-in is completed.'],
    ['<b>Check-out</b>', 'The guest has started checking out.', 'Set automatically after check-out verification (step 2).'],
    ['<b>Inspection</b>', 'The room has been inspected and the charges saved.', 'Set automatically after the inspection is saved (step 3).'],
    ['<b>Cleaning</b> or <b>Maintenance</b>', 'The room is being cleaned or needs repair.',
     'Set automatically when the check-out is recorded. Reception can also set these by hand.'],
], [0.24, 0.36, 0.40])

h2('Things that work the same on every screen')
bullets([
    'The <b>menu on the left</b> opens each page. The page you are on is highlighted.',
    'A red star (<font color="#c9423d">*</font>) beside a box means it must be filled in.',
    'A <b>green message</b> at the top of the page means the action was saved. A <b>red message</b> explains what must be fixed.',
    'Long tables show a few rows at a time. Use <b>Previous</b> and <b>Next</b> under the table to see the rest.',
    'Before anything is deleted, cancelled or paid, a small box asks you to confirm. Nothing happens until you confirm.',
    '<b>Logout</b> is at the bottom of the menu. Log out when you leave the computer.',
])

# =====================================================================
# 2. SIGNING IN
# =====================================================================

part('Getting started|2. Signing in and passwords')
p('You need an account made by the admin. You choose your own password; the admin never sees it.', 'lead')

h2('Log in')
steps([
    'Open the system in your browser. The login page appears.',
    'Type your <b>email</b> and your <b>password</b>.',
    'To check what you typed, click the <b>eye</b> at the right of the password box. Click it again to hide the password.',
    'Click <b>Login</b>. An admin goes to the Admin Dashboard; reception goes to the Reception Dashboard.',
])
shot('01-login', 'The login page. The eye in the password box shows or hides what you type.')
box('If the page says "This account is deactivated", ask the admin to turn your account back on.')

h2('Set your password the first time')
p('When the admin creates your account, the system sends an email titled "Set your Guest Accommodation password".')
steps([
    'Open the email and click <b>Set my password</b>.',
    'Type a <b>new password</b> of at least 8 characters, then type it again in <b>Repeat the password</b>.',
    'Click <b>Save password</b>. You are taken back to the login page and can log in.',
])
shot('03-set-password', 'The page that opens from the email link.')
box('The link works for 60 minutes and only once. If it has expired, use "Forgot password?" or ask the admin to send a new link.', 'warn')

h2('If you forgot your password')
steps([
    'On the login page, click <b>Forgot password?</b>',
    'Type your email and click <b>Send link</b>.',
    'Open the email you receive and follow the same steps as "Set your password the first time".',
])
shot('02-forgot-password', 'Ask for a new password link.')

# =====================================================================
# PART A: ADMIN
# =====================================================================

part('For the admin|3. Admin Dashboard')
p('The first page an admin sees. It is a summary; nothing is changed here.', 'lead')
shot('04-admin-dashboard', 'The Admin Dashboard: counts, rooms by status, and today at the front desk.', WIDTH * 0.74)
bullets([
    '<b>Tiles at the top:</b> number of locations, rooms and total capacity, occupancy, guests staying now, and active reception accounts.',
    '<b>Rooms by status:</b> how many rooms are Available, Occupied, in Check-out, Inspection, Cleaning or Maintenance.',
    '<b>Today at the front desk:</b> check-ins and check-outs today, reservations waiting to arrive, IDs held at reception, and charges collected this month.',
    '<b>Buttons at the top right:</b> shortcuts to add a room, a location or an account.',
])
shot('05-admin-dashboard-lower', 'Lower part: every location with its rooms, recent guest activity, and the accounts.', WIDTH * 0.74)
bullets([
    '<b>Locations and their rooms:</b> one row per location. <b>View rooms</b> opens its room list; <b>+ Add room</b> adds a room to it.',
    '<b>Recent guest activity:</b> the latest stays and their status.',
    '<b>Accounts:</b> who can log in and whether the account is active.',
])

part('For the admin|4. Locations')
p('A location is a building or area, such as Barracks or Guest Villa. Every room belongs to one location, so add the location first.', 'lead')
shot('11-locations', 'The Locations page: the form to add a location, and the list of all locations.')
h2('Add a location')
steps([
    'Open <b>Locations</b> in the menu.',
    'Type the <b>Location name</b>. A description is optional.',
    'Click <b>Add location</b>. It appears in the list below.',
])
h2('Change or remove a location')
bullets([
    '<b>Edit:</b> click Edit on its row, change the name or description, then click <b>Save changes</b>.',
    '<b>+ Add room:</b> opens the Add room page with this location already chosen.',
    '<b>Delete:</b> removes the location after you confirm.',
])
box('A location that still has rooms cannot be deleted. Delete or move its rooms first.', 'warn')

part('For the admin|5. Rooms')
p('Rooms are what guests are assigned to. Each room has a capacity, one or more rates, and a list of what is included.', 'lead')
shot('07-rooms', 'The Rooms page. Choose a location at the top to list only its rooms.')
bullets([
    'The table shows 10 rooms at a time. Use <b>Previous</b> and <b>Next</b> under it when there are more.',
    'Use the <b>All locations</b> box to list the rooms of one location only.',
    '<b>Edit</b> opens the room so you can change it. <b>Delete</b> removes it after you confirm.',
])
box('A room that has reservations or guest records cannot be deleted, so the guest log stays complete.', 'warn')

h2('Add a room')
steps([
    'Click <b>+ Add room</b> at the top right.',
    'Type the <b>Room No</b> (for example A-101) and choose its <b>Location</b>.',
    'Type the <b>Capacity</b>: the most guests the room can take.',
    'Leave <b>Status</b> as Available unless the room is not ready yet.',
    'Under <b>Rates</b>, type at least one price: <b>Per night</b>, <b>Per hour</b> or <b>Daily</b>. Leave a box empty if the room does not offer that rate.',
    'Under <b>Inclusions</b>, type an item (for example Television) and press Enter or click <b>+ Add</b>. Repeat for each item.',
    'Click <b>Add room</b>.',
])
shot('08-add-room', 'Add room: room information and rates.')
shot('09-add-room-inclusions', 'Add room: inclusions, then the Add room button.')
box('Reception can only charge a stay with a rate the room has. A room with only a Daily rate cannot be checked in "Per night".')

h2('Change a room')
p('Click <b>Edit</b> on the room\'s row. The same form opens with the room\'s details filled in. Change what you need and click <b>Save changes</b>.')
shot('10-edit-room', 'Editing room A-101.')

part('For the admin|6. Accounts')
p('Create a login for each person who uses the system. You do not set or see their password.', 'lead')
shot('06-accounts', 'The Accounts page.')
h2('Create an account')
steps([
    'Open <b>Accounts</b> in the menu.',
    'Type the person\'s <b>Name</b> and <b>Email</b>. Use an email they can open.',
    'Choose the <b>Role</b>: Reception for front-desk staff, Admin for someone who sets the system up.',
    'Click <b>Create account</b>. The system emails them a link to set their own password.',
])
h2('Manage an account')
bullets([
    '<b>Edit:</b> change the name, email or role.',
    '<b>Send password link:</b> emails a new link, for someone who forgot their password or whose first link expired.',
    '<b>Deactivate:</b> the person can no longer log in. Their past records stay in the guest log. <b>Activate</b> turns the account back on.',
])
box('You cannot deactivate your own account or remove your own admin role, so the system always has an admin.')

# =====================================================================
# PART B: RECEPTION
# =====================================================================

part('For reception|7. Reception Dashboard')
p('The first page reception sees. It shows what needs attention now and is the fastest way to start a check-in or check-out.', 'lead')
shot('12-reception-dashboard', 'Top of the Dashboard: the counts and the check-out alerts.', WIDTH * 0.78)
bullets([
    '<b>Tiles:</b> total rooms, available, occupied, rooms in check-out / inspection / cleaning, guests to arrive, and guests due to leave.',
    '<b>Check-out alerts:</b> guests who have passed their expected check-out time, or will reach it within two hours. Click <b>Check out</b> or <b>Continue check-out</b> to open that guest.',
    '<b>+ Check in a guest</b> and <b>Check out a guest</b> at the top right open those pages.',
])

h2('The room cards')
shot('13-dashboard-rooms', 'The Rooms section: three filters, then the room cards of one location.', WIDTH * 0.78)
bullets([
    'The cards are shown <b>one location at a time</b>. The buttons under the cards go to the next or previous location.',
    'Use the three boxes to narrow the list: by <b>location</b>, by <b>status</b>, and by <b>number of guests</b> ("Good for 4 or more"). <b>Clear</b> removes the filters.',
    'Each card shows the room, its status, how many guests it is good for (or who is staying in it), its rates and its inclusions.',
    'The coloured edge matches the status: green Available, red Occupied, orange Check-out, purple Inspection, teal Cleaning, grey Maintenance.',
])
h3('Get room: check a guest in to a room you picked')
steps([
    'Find an <b>Available</b> room, using the filters if needed.',
    'Click the green <b>Get room</b> button on its card.',
    'The Check-in page opens with that room already chosen. Continue with the steps in chapter 9.',
])
h3('Change a room\'s status')
steps([
    'Click the room card (anywhere except the button). A box opens with the room\'s rates and inclusions.',
    'Under <b>Change status to</b>, choose Available, Cleaning or Maintenance.',
    'Click <b>Save status</b>.',
])
box('A room that is Occupied, in Check-out or in Inspection cannot be changed by hand. Finish the guest\'s check-out first; the box gives a button to open it.', 'warn')

h2('The lists below the rooms')
shot('14-dashboard-lists', 'Guests to arrive, guests due to leave, and rooms needing attention.', WIDTH * 0.74)
bullets([
    '<b>Guests to arrive:</b> reservations for today or earlier that have not checked in. <b>Check in</b> opens the check-in with the reservation filled in.',
    '<b>Guests due to leave:</b> guests whose expected check-out is today or already past.',
    '<b>Rooms needing attention:</b> rooms in Check-out, Inspection, Cleaning or Maintenance. When a room is ready, set it to Available from its card.',
])

part('For reception|8. Reservations')
p('Use this page for guests who book before they arrive. It lists advance bookings only; once a guest is checked in, their record moves to the Check-out page.', 'lead')
shot('28-reservations', 'The Reservations page.')
bullets([
    'The list shows <b>Reserved</b> bookings. Use the box beside the search to see <b>Cancelled</b> ones, or both.',
    'Type a guest, company or room in the search box and click <b>Search</b>.',
])

h2('Make a reservation')
steps([
    'Click <b>+ New reservation</b> at the top right.',
    'Type the <b>Guest name</b>. Company, contact number and email are optional.',
    'Choose the <b>Room</b> and the <b>Number of guests</b>.',
    'Set the <b>Check-in</b> and the <b>Expected check-out</b> date and time.',
    'Click <b>Save reservation</b>.',
])
shot('29-new-reservation', 'The New reservation form.')
box('The system refuses a reservation if the room is already booked for those dates, or if the guests are more than the room\'s capacity. The red message says which.', 'warn')

h2('What you can do with a reservation')
table(['Button', 'What it does'], [
    ['<b>Check in</b>', 'Opens the Check-in page with this reservation\'s details already filled in. Use it when the guest arrives.'],
    ['<b>Cancel</b>', 'Marks the reservation as Cancelled, after you confirm. The room becomes free for those dates.'],
    ['<b>Estimate</b>', 'Shows the estimated bill for the stay (shown when the room has a per-night rate).'],
    ['<b>View</b>', 'Shows all the details of the reservation in a box.'],
    ['<b>Delete</b>', 'Removes the reservation completely, after you confirm. Use Cancel instead if you want to keep a record.'],
], [0.2, 0.8])
shot('31-reservation-details', 'View: the details of a reservation.')
shot('30-estimated-bill', 'Estimate: what the stay is expected to cost. Print bill prints it.')

part('For reception|9. Check-in')
p('Check-in follows the company\'s eight steps. Steps 1 and 2 (the guest arrives and reports to reception) happen in person; '
  'the system starts at step 3 and shows one step at a time.', 'lead')
bullets([
    'The bar at the top shows where you are. A step turns <b>green with a tick</b> when it is complete.',
    'Click <b>Next step</b> to go on. If something is missing, the system points at the box to fill in.',
    'Click <b>Back</b>, or a step in the bar, to return to an earlier step.',
    'Nothing is saved until you click <b>Complete check-in</b> on the last step.',
])

h2('Step 3: Fill out the check-in form')
steps([
    'Open <b>Check-in</b> in the menu (or use <b>Get room</b> on the Dashboard, or <b>Check in</b> on a reservation).',
    '<b>Has a reservation?</b> Choose the guest\'s reservation to fill the form in, or leave it as "No reservation (walk-in guest)".',
    'Fill in the <b>Guest name</b>, <b>Guest type</b>, <b>Contact number</b> and <b>Address</b>. Company is optional.',
    'Type the <b>Number of guests</b>.',
    'Set the <b>Expected check-out</b> date and time.',
    'Add any <b>Purpose / notes</b>, then click <b>Next step</b>.',
])
shot('15-checkin-form', 'Step 3: the check-in form.')
h3('When there are two or more guests')
p('If the number of guests is 2 or more, a section called <b>Other guests in the group</b> appears. '
  'Fill in the <b>name, address and contact number</b> of each other guest. The guest at the top of the form counts as guest 1. '
  'All of these are required.')
shot('16-checkin-form-lower', 'Lower part of step 3, with the Next step button. Guests already checked in are listed underneath.')

h2('Step 4: Verification')
p('Check that the guest is expected and that the details are correct. Tick '
  '<b>"I have verified the guest\'s identity and the details on the form"</b>, then click <b>Next step</b>.')
shot('17-checkin-verification', 'Step 4: verification.')

h2('Step 5: Surrender valid ID')
steps([
    'Choose the <b>Type of ID</b> the guest gives you and type the <b>ID number</b>.',
    'Keep the ID at reception and tick <b>"The guest surrendered the ID"</b>.',
    'Click <b>Next step</b>.',
])
shot('18-checkin-id', 'Step 5: the ID that reception keeps until check-out.')

h2('Step 6: Assign accommodation')
steps([
    'Choose the <b>Rate type</b>: Per night, Per hour or Daily.',
    'Choose the <b>Room</b>. Only Available rooms are listed. If you came from <b>Get room</b>, it is already chosen.',
    'Read the <b>Estimated accommodation cost</b> to tell the guest what to expect.',
    'Click <b>Complete check-in</b>.',
])
shot('19-checkin-room', 'Step 6: rate type, room and the estimated cost.')
bullets([
    '<b>If the room does not have the rate you chose,</b> a red message lists the rates that room does have. Choose one of those, or pick another room. The room stays selected.',
    '<b>If the group is bigger than the room,</b> a red message says how many guests still need a room and suggests rooms to add. Tick them under <b>Additional rooms for this group</b>. Each room gets its own record and slip.',
])

h2('Steps 7 and 8: Room assignment slip')
p('After the check-in is saved, the room assignment slip appears. The room is now <b>Occupied</b>.')
steps([
    'Click <b>Print slip</b> and give the slip to the guest with the key.',
    'The guest proceeds to the room.',
    'Click <b>Check in another guest</b> or <b>Back to dashboard</b>.',
])
shot('20-room-slip', 'The room assignment slip, with the guest list and the room.', WIDTH * 0.72)

part('For reception|10. Check-out')
p('The Check-out page lists everyone who has checked in. Each guest is checked out in eight steps that must be done in order.', 'lead')
shot('21-checkout-list', 'The Check-out page: guests in house first, then guests who already checked out.')
bullets([
    '<b>Tiles:</b> how many guests are Overdue, Due today and Upcoming. A red message appears when a guest is past the expected check-out date.',
    '<b>Guests in house:</b> guests staying now, the soonest to leave first. Overdue rows are red; rows due today are orange.',
    '<b>Checked out:</b> past guests, the most recent first. Search by guest, company or room, or change the order with the box beside it.',
    '<b>View bill</b> opens the guest\'s bill at any time.',
])

h2('Step 1 and 2: The guest reports to reception, and verification')
steps([
    'On the guest\'s row click <b>Check out</b> (or <b>Continue check-out</b> if it was already started).',
    'Check that it is the right guest and room, shown at the top of the page.',
    'Click <b>Confirm and continue</b>. The room status changes to Check-out.',
])
shot('22-checkout-verification', 'A guest\'s check-out page. Only the current step can be filled in; later steps are greyed out.')

h2('Steps 3 and 4: Room inspection and additional charges')
steps([
    'Inspect the room. Type what you found in <b>Inspection notes</b>, and any damage in the next box.',
    'Choose what the room needs after check-out: <b>Cleaning</b> or <b>Maintenance</b>.',
    'For extra services, damages or other charges, click <b>+ Add charge</b>, choose the kind, type a description and the amount. Add one line for each charge.',
    'Click <b>Save inspection and charges</b>. The room status changes to Inspection.',
])
shot('23-checkout-inspection', 'Step 3: inspection notes and additional charges.')

h2('Step 5: Billing and payment')
p('The bill is shown as a list: accommodation, additional charges, total, amount already paid and the <b>balance due</b>.')
steps([
    'Collect the payment from the guest.',
    'The <b>Payment received</b> box already shows the balance. Change it only if the guest pays a different amount.',
    'Click <b>Record payment</b> and confirm.',
])
shot('24-checkout-billing', 'Step 5: the bill summary and the payment.')
box('The ID can only be returned when the bill is paid in full. If the guest pays part of it, that amount is kept on the bill and this step stays open for the rest.', 'warn')

h2('Step 6: Return surrendered ID')
p('The page shows which ID to return. Give it back to the guest, then click <b>ID returned to guest</b>.')
shot('25-checkout-return-id', 'Step 6: return the ID that was kept at check-in.')

h2('Steps 7 and 8: Record the check-out; the guest leaves')
steps([
    'Type any <b>Final notes</b> (optional).',
    'Click <b>Record check-out</b> and confirm.',
    'The guest may now leave. The stay is saved in the Guest Log and the room goes to Cleaning or Maintenance.',
    'When the room is ready, set it to <b>Available</b> from its card on the Dashboard.',
])
shot('26-checkout-record', 'Step 7: record the check-out.')

h2('The guest bill')
p('<b>View bill</b> opens the bill for a stay: the guest and stay details, each charge with how it was calculated, the total, '
  'the amount paid and the balance. Click <b>Print bill</b> to print it for the guest.')
shot('27-guest-bill', 'A paid guest bill.', WIDTH * 0.78)

part('For reception|11. Calendar')
p('A month view of who arrives and who is expected to leave on each day.', 'lead')
shot('32-calendar', 'The Calendar for one month.')
bullets([
    '<b>Blue</b> entries are arrivals and reservations. <b>Orange</b> entries are expected check-outs.',
    'Each entry shows the guest, the room, the time and the status of the stay.',
    'The two counts at the top total the arrivals and the expected check-outs for the month.',
    'Use <b>Previous</b>, <b>Today</b> and <b>Next</b> to change the month. Today is shaded.',
    'Click a day to review its schedule.',
])

part('For reception|12. Guest Log')
p('The permanent record of every guest who checked in, with their check-out and payment.', 'lead')
shot('33-guest-log', 'The Guest Log.')
bullets([
    '<b>Tiles:</b> total stays, guests in house now, guests checked out, and IDs still held at reception.',
    '<b>Guest:</b> the name, with the guest type and company underneath.',
    '<b>Check-in / Check-out:</b> the date and time. A guest who has not left shows "Still in house".',
    '<b>ID:</b> Held while reception keeps it, Returned after check-out.',
    '<b>Bill total, Paid, Balance:</b> the money for the stay. The small line under the total shows the room charge and any extra charges.',
    '<b>Payment:</b> Paid, Partially Paid or Unpaid.',
    '<b>View bill</b> opens the bill for that stay.',
    'The table shows 5 stays at a time; use <b>Previous</b> and <b>Next</b>.',
])
h2('Export the log')
p('Click <b>Export to CSV</b> at the top right. A file is downloaded that opens in Excel, with every stay and all its details in separate columns.')

# =====================================================================
# QUICK ANSWERS
# =====================================================================

part('Help|13. Common questions')
table(['Question', 'Answer'], [
    ['I cannot log in.',
     'Check the email and use the eye to check the password. If you forgot it, click <b>Forgot password?</b> If the page says the account is deactivated, ask the admin.'],
    ['The password link says it is not valid any more.',
     'Links last 60 minutes and work once. Ask for a new one with <b>Forgot password?</b>, or ask the admin to click <b>Send password link</b>.'],
    ['The room I want is not in the check-in list.',
     'Only Available rooms are listed. If the room is clean and ready, set it to Available from its card on the Dashboard.'],
    ['Check-in says the room has no rate.',
     'The room does not offer the rate type you chose. Choose one of the rates listed in the red message, or ask the admin to add the rate to the room.'],
    ['Check-in says the room cannot take all the guests.',
     'The group is bigger than the room\'s capacity. Tick more rooms under <b>Additional rooms for this group</b>, or choose a bigger room.'],
    ['I cannot change a room\'s status.',
     'A room that is Occupied, in Check-out or in Inspection changes only through the guest\'s check-out. Finish the check-out first.'],
    ['I cannot return the ID.',
     'The bill is not fully paid. Record the remaining payment in step 5 first.'],
    ['A guest wants to cancel a booking.',
     'On the Reservations page click <b>Cancel</b> on their row. Only a Reserved booking can be cancelled.'],
    ['I cannot find a guest on the Reservations page.',
     'A guest who has checked in is no longer a reservation. Look on the <b>Check-out</b> page or in the <b>Guest Log</b>.'],
    ['I cannot delete a room or a location.',
     'A location with rooms, and a room with guest records, cannot be deleted. This protects the guest log.'],
    ['I made a mistake in a step of the check-in.',
     'Before clicking Complete check-in, click <b>Back</b> or the step in the bar, and correct it.'],
    ['A page I need is not in my menu.',
     'Reception accounts see only the front desk, and admin accounts see only the setup pages. Ask the admin if you need a different role.'],
], [0.36, 0.64])
box('For anything not covered here, contact your system administrator.')


# =====================================================================
# PAGE LAYOUT
# =====================================================================

class Manual(BaseDocTemplate):
    """Adds every chapter and section heading to the contents page and the PDF bookmarks."""

    def afterFlowable(self, flowable):
        if isinstance(flowable, Paragraph) and flowable.style.name in ('h1', 'h2'):
            level = 0 if flowable.style.name == 'h1' else 1
            text = flowable.getPlainText()
            key = f'h{self.seq.nextf("heading")}'
            self.canv.bookmarkPage(key)
            self.canv.addOutlineEntry(text, key, level=level, closed=level == 0)
            self.notify('TOCEntry', (level, text, self.page, key))


def cover(canvas, doc):
    width, height = A4
    canvas.saveState()
    canvas.setFillColor(NAVY)
    canvas.rect(0, height * 0.42, width, height * 0.58, stroke=0, fill=1)
    canvas.setFillColor(TEAL)
    canvas.rect(0, height * 0.42 - 5, width, 5, stroke=0, fill=1)

    canvas.setFillColor(colors.white)
    canvas.setFont('Body-Bold', 34)
    canvas.drawString(MARGIN, height * 0.66, TITLE)
    canvas.setFont('Body', 22)
    canvas.drawString(MARGIN, height * 0.66 - 36, SUBTITLE)
    canvas.setFillColor(colors.HexColor('#a9bbd9'))
    canvas.setFont('Body', 12)
    canvas.drawString(MARGIN, height * 0.66 - 66, 'For reception staff and administrators')

    canvas.setFillColor(TEXT)
    canvas.setFont('Body-Bold', 13)
    canvas.drawString(MARGIN, height * 0.34, COMPANY)
    canvas.setFillColor(MUTED)
    canvas.setFont('Body', 11)
    canvas.drawString(MARGIN, height * 0.34 - 18, 'Reservations  ·  Check-in  ·  Check-out  ·  Billing  ·  Guest Log')
    canvas.drawString(MARGIN, MARGIN, VERSION)
    canvas.restoreState()


def page(canvas, doc):
    width, height = A4
    canvas.saveState()
    canvas.setStrokeColor(BORDER)
    canvas.setLineWidth(0.6)
    canvas.line(MARGIN, height - 13 * mm, width - MARGIN, height - 13 * mm)
    canvas.line(MARGIN, 13 * mm, width - MARGIN, 13 * mm)
    canvas.setFillColor(MUTED)
    canvas.setFont('Body', 8.5)
    canvas.drawString(MARGIN, height - 11 * mm, f'{TITLE} – {SUBTITLE}')
    canvas.drawRightString(width - MARGIN, height - 11 * mm, COMPANY)
    canvas.drawString(MARGIN, 9 * mm, VERSION)
    canvas.drawRightString(width - MARGIN, 9 * mm, f'Page {doc.page}')
    canvas.restoreState()


document = Manual(
    str(OUTPUT), pagesize=A4, leftMargin=MARGIN, rightMargin=MARGIN, topMargin=17 * mm, bottomMargin=17 * mm,
    title=f'{TITLE} – {SUBTITLE}', author=COMPANY, subject='How to use the Guest Accommodation System',
)
frame = Frame(MARGIN, 17 * mm, WIDTH, A4[1] - 34 * mm, leftPadding=0, rightPadding=0, topPadding=0, bottomPadding=0)
document.addPageTemplates([
    PageTemplate(id='cover', frames=[frame], onPage=cover, autoNextPageTemplate='page'),
    PageTemplate(id='page', frames=[frame], onPage=page),
])

# the cover is an empty first page drawn by cover(); everything else follows
document.multiBuild([Spacer(1, 1)] + story)
print(f'Saved {OUTPUT}')
