<?php /* personal-information inputs; expects $v = current values */ ?>
<label>First name
    <input type="text" name="first_name" maxlength="50" required value="<?= e($v['first_name'] ?? '') ?>">
</label>
<label>Last name
    <input type="text" name="last_name" maxlength="50" required value="<?= e($v['last_name'] ?? '') ?>">
</label>
<label>Age
    <input type="number" name="age" min="1" max="120" required value="<?= e($v['age'] ?? '') ?>">
</label>
<label>Contact number
    <input type="text" name="contact_number" maxlength="20" value="<?= e($v['contact_number'] ?? '') ?>">
</label>
<label class="full">Email
    <input type="email" name="email" maxlength="100" value="<?= e($v['email'] ?? '') ?>">
</label>
<label class="full">Address
    <input type="text" name="address" maxlength="255" value="<?= e($v['address'] ?? '') ?>">
</label>
