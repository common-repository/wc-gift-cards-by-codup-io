
<label for="recipient_name">Recipient name</label>

<input type="text" class="codup-gc-text" name="recipient_name" id="recipient_name" value="<?=isset($_POST['recipient_name'])?$_POST['recipient_name']:''?>"  required />

<label for="recipient_email">Recipient email</label>

<input type="email" class="codup-gc-text"  name="recipient_email"  id="recipient_email" value="<?=isset($_POST['recipient_email'])?$_POST['recipient_email']:''?>"  required />

<label for="recipient_message">Message</label>

<textarea name="recipient_message" class="codup-gc-text"  id="recipient_message" placeholder="Be precise, you only have 500 characters." rows="2" cols="5" maxlength="500"><?=isset($_POST['recipient_message'])?$_POST['recipient_message']:''?></textarea>
<style type="text/css">
    table.variations.codup-gc-amount {
    border-collapse : unset;
    }
    .codup-gc-text {
        margin-top: 10px;
        margin-bottom: 10px;
        width: 100%;
    }
    form.cart input.codup-gc-text {
    width: 100%;
}

</style>