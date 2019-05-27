function HandlePopupResult(loid,loname) {
    // alert("result of popup is: " + result);
    document.getElementById('id_loid').value=loid
    document.getElementById('id_loname').value=loname
}
function openBrowser() {
  window.open("/mod/goone/browser.php");
}