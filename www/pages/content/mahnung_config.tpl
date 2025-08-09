<form action="" method="post">
<fieldset><legend>{|Mahnung Einstellungen|}</legend>
<table>
  <tr>
    <td>{|Absender|}:</td>
    <td><input type="text" name="sender" value="[SENDER]" size="40"></td>
  </tr>
  <tr>
    <td>{|E-Mail Betreff|}:</td>
    <td><input type="text" name="email_subject" value="[EMAIL_SUBJECT]" size="60"></td>
  </tr>
  <tr>
    <td valign="top">{|E-Mail Vorlage|}:</td>
    <td><textarea name="email_template" cols="60" rows="6">[EMAIL_TEMPLATE]</textarea></td>
  </tr>
  <tr>
    <td valign="top">{|SMS Vorlage|}:</td>
    <td><textarea name="sms_template" cols="60" rows="4">[SMS_TEMPLATE]</textarea></td>
  </tr>
</table>
</fieldset>

<fieldset><legend>{|Analyse|}</legend>
<table>
  <tr>
    <td>{|Durchschnittliche Verz&ouml;gerung|}:</td><td>[AVG_DELAY] {|Tage|}</td>
  </tr>
  <tr>
    <td>{|Maximale Verz&ouml;gerung|}:</td><td>[MAX_DELAY] {|Tage|}</td>
  </tr>
</table>
</fieldset>

<input type="submit" name="submit" value="{|Speichern|}">
</form>
