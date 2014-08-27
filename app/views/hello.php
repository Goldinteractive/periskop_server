<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Periskop debugger</title>
	<style>
		@import url(//fonts.googleapis.com/css?family=Lato:700);

		body {
			margin:0;
			font-family:'Lato', sans-serif;
			text-align:center;
			color: #999;
		}

		.welcome {
			width: 300px;
			height: 200px;
			position: absolute;
			left: 50%;
			top: 50%;
			margin-left: -150px;
			margin-top: -100px;
		}

		a, a:visited {
			text-decoration:none;
		}

		h1 {
			font-size: 32px;
			margin: 16px 0 0 0;
		}
	</style>
</head>
<body>
	<div id="debug">

	</div>
	<script>
		var debug = document.getElementById('debug'),
			connection = new WebSocket('ws://178.62.185.145:55555'),
			onSocketOpened = function (e) {
				connection.send(JSON.stringify([5, 'debug']));
			},
			onSocketMessage =function (e) {
				debug.innerHTML = debug.innerHTML + '<br />' + JSON.stringify(JSON.parse(e.data)[2].log_msg || 'No cool messages to show',4);
			},
			onSocketError = function (e) {
				alert('error');
				console.log(e);
			};
        // listen the websocket events
        connection.onopen = onSocketOpened;
        connection.onmessage = onSocketMessage;
        connection.onerror = onSocketError;
        connection.onclose = onSocketError;
	</script>
</body>
</html>
