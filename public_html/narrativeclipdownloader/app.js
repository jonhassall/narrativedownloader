/* jshint esnext: true */
var CLIENT_ID = 'nR9Qw0nymH0ZUe4XHDJi8arT0KQDDwGezyYxbHpF';

var getHashVars = function() {
  if (window.location.hash) {
    return window.location.hash.split('#')[1].split('&').reduce((params, str) => {
      parts = str.split('=');
      params[parts[0]] = parts[1];
      return params;
    }, {});
  } else {
    return {};
  }
};

var getLogin = function() {
  var hashVars = getHashVars();
  if (hashVars.hasOwnProperty('access_token')) {
    return hashVars.access_token;
  } else {
    var redirect_uri = window.location.origin + window.location.pathname;
    window.location = 'https://narrativeapp.com/oauth2/authorize?response_type=token&client_id=' + CLIENT_ID + '&redirect_uri=' + redirect_uri;
  }
};


var main = function() {
  new Clipboard('.btn');

  var access_token = getLogin();
  $('#access_token').val(access_token);
  $('#access_token').show();
};

$( document ).ready(function() {
  main();
});