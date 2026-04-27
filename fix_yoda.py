import re

# Simpler, more reliable approach
# Match: $var_or_expr (===|!==) literal_or_constant
# $var: starts with $, followed by word chars and optional ->prop or ['key'] chains
# Literal: 'str', "str", false, true, null, integer, or ALL_CAPS constant

# Group 1: variable expression (starts with $, may have ->prop or ['key'] etc.)
# Group 2: operator
# Group 3: right-hand literal/constant

YODA_RE = re.compile(
    r'(\$\w+(?:(?:->\w+)|(?:\[[\w\'"]+\]))*)'  # LHS variable expr
    r'(\s*(?:===|!==)\s*)'                       # operator
    r"((?:'[^']*'|\"[^\"]*\"|\bfalse\b|\btrue\b|\bnull\b|-?\d+\b|[A-Z_]{2,}\b))"  # RHS literal
)

def fix_yoda(line):
    def swap(m):
        var, op, lit = m.group(1), m.group(2), m.group(3)
        return lit + op + var
    return YODA_RE.sub(swap, line)

# Quick test
tests = [
    ("        if ( $enable_tickets !== '1' || ! $product_id ) {",
     "        if ( '1' !== $enable_tickets || ! $product_id ) {"),
    ("        if ( $ts === false ) {",
     "        if ( false === $ts ) {"),
    ("        if ( $key === 'title' ) {",
     "        if ( 'title' === $key ) {"),
    ("        if ( $post->post_type !== 'event' ) {",
     "        if ( 'event' !== $post->post_type ) {"),
    ("        if ( $node->nodeType === XML_TEXT_NODE ) {",
     "        if ( XML_TEXT_NODE === $node->nodeType ) {"),
    ("        if ( $capacity_raw === '' || $capacity_raw === false ) {",
     "        if ( '' === $capacity_raw || false === $capacity_raw ) {"),
    ("                'status' => $require_approval === '1' ? 'pending' : 'confirmed',",
     "                'status' => '1' === $require_approval ? 'pending' : 'confirmed',"),
    # Should NOT change already-correct Yoda:
    ("        if ( 'publish' === $post->post_status ) {",
     "        if ( 'publish' === $post->post_status ) {"),
]

print("=== TESTS ===")
all_pass = True
for inp, expected in tests:
    result = fix_yoda(inp)
    status = "OK" if result == expected else "FAIL"
    if status == "FAIL":
        all_pass = False
        print(status + ": " + inp)
        print("  Expected: " + expected)
        print("  Got:      " + result)
    else:
        print(status + ": " + inp[:60])

if not all_pass:
    print("\nFix the regex before running on files!")
    exit(1)

print("\n=== APPLYING TO FILES ===")

files = [
    'includes/class-wpevents-woocommerce.php',
    'includes/class-wpevents-admin.php',
    'includes/class-wpevents-shortcodes.php',
    'includes/class-wpevents-additional-features.php',
    'includes/class-wpevents-recurrence.php',
    'includes/class-wpevents-cpt.php',
    'includes/class-wpevents-schema.php',
    'includes/class-wpevents-blocks-clean.php',
    'includes/class-wpevents-blocks.php',
    'includes/class-wpevents-organizer-capabilities.php',
    'includes/class-wpevents-import-tribe.php',
    'includes/class-wpevents-ical.php',
    'includes/class-wpevents-filters.php',
    'includes/template-functions.php',
]

total = 0
for fpath in files:
    try:
        with open(fpath, 'r') as fh:
            lines = fh.readlines()
    except FileNotFoundError:
        continue

    new_lines = []
    count = 0
    for lineno, line in enumerate(lines, 1):
        new_line = fix_yoda(line)
        if new_line != line:
            count += 1
        new_lines.append(new_line)

    if count:
        with open(fpath, 'w') as fh:
            fh.writelines(new_lines)
        print(fpath + ': fixed ' + str(count))
        total += count

print('Total Yoda fixes: ' + str(total))
