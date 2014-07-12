<form id="ocsafe_settings">
    <fieldset class="personalblock">
        <h2>ocSafe</h2>
        <div>
            <input type="checkbox" id="scrambleFileName" name="scrambleFileName" <?php p($_['scrambleFileName']) ?> />
            <label for="scrambleFileName"><?php p($l->t('Scramble file name when encrypting')) ?></label>
        </div>
    </fieldset>
</form>
