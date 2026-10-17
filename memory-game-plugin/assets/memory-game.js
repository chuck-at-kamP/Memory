/* Memory game shell for WordPress. Config (restUrl, nonce) comes from wp_localize_script. */
(function () {
	'use strict';

	var root = document.getElementById('memory-game-root');
	if (!root || typeof MemoryGameConfig === 'undefined') { return; }

	// Placeholder content: replace/extend with the full fact + question set from index.html.
	var SYMBOLS = ['🍎', '🍌', '🍇', '🍒', '🐶', '🐱', '⭐', '🚗'];
	var QUESTIONS = {
		'🍎': { text: 'Weltweit gibt es über 7.500 verschiedene Apfelsorten.', question: 'Wie viele Apfelsorten gibt es weltweit?', answers: ['Etwa 750', 'Über 7.500', 'Über 75.000'], correct: 1 },
		'🍌': { text: 'Bananen sind botanisch gesehen Beeren.', question: 'Was sind Bananen botanisch gesehen?', answers: ['Nüsse', 'Beeren', 'Steinfrüchte'], correct: 1 }
		// ...add one entry per symbol
	};

	var sessionId = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() :
		String(Date.now()) + '-' + Math.random().toString(36).slice(2);
	var round = 0, attempts = 0, startTime = 0, duration = 0, found = 0, open = [], locked = false;
	var cards = [], symbol = null;
	var usedSymbols = [];

	function shuffle(a) {
		for (var i = a.length - 1; i > 0; i--) {
			var j = Math.floor(Math.random() * (i + 1));
			var t = a[i]; a[i] = a[j]; a[j] = t;
		}
		return a;
	}

	function el(tag, cls, text) {
		var e = document.createElement(tag);
		if (cls) { e.className = cls; }
		if (text !== undefined) { e.textContent = text; }
		return e;
	}

	function submitResult(payload) {
		return fetch(MemoryGameConfig.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': MemoryGameConfig.nonce },
			body: JSON.stringify(payload)
		}).then(function (r) { return r.json().then(function (b) { return { ok: r.ok, body: b }; }); });
	}

	function startRound() {
		var pool = Object.keys(QUESTIONS).filter(function (s) { return usedSymbols.indexOf(s) === -1; });
		if (!pool.length) {
			root.appendChild(el('p', 'mg-message', 'Alle Runden abgeschlossen.'));
			return;
		}
		symbol = pool[Math.floor(Math.random() * pool.length)];
		usedSymbols.push(symbol);
		round++; attempts = 0; found = 0; open = []; locked = false; startTime = 0;
		cards = shuffle(SYMBOLS.concat(SYMBOLS));
		render();
	}

	function render() {
		root.textContent = '';
		var info = el('div', 'mg-info', 'Spiel ' + round + ' | Versuche: ' + attempts);
		var message = el('div', 'mg-message', ' ');
		var table = el('table', 'mg-board');
		var tr;
		cards.forEach(function (c, i) {
			if (i % 4 === 0) { tr = table.insertRow(); }
			var td = tr.insertCell();
			td.addEventListener('click', function () { flip(td, c, info, message); });
		});
		root.appendChild(info);
		root.appendChild(table);
		root.appendChild(message);
		root.appendChild(el('div', 'mg-question'));
		root.appendChild(el('div', 'mg-stats'));
	}

	function flip(td, c, info, message) {
		if (locked || td.className.match(/open|found/)) { return; }
		if (!startTime) { startTime = Date.now(); }
		td.textContent = c; td.className = 'open'; open.push(td);
		if (open.length < 2) { return; }
		var a = open[0], b = open[1];
		open = []; attempts++;
		info.textContent = 'Spiel ' + round + ' | Versuche: ' + attempts;
		if (a.textContent === b.textContent) {
			a.className = b.className = 'found'; found++;
			message.textContent = (QUESTIONS[c] && QUESTIONS[c].text) || '';
			if (found === SYMBOLS.length) {
				duration = Date.now() - startTime;
				setTimeout(askQuestion, 1500);
			}
		} else {
			locked = true;
			setTimeout(function () {
				a.className = b.className = ''; a.textContent = b.textContent = ''; locked = false;
			}, 1000);
		}
	}

	function askQuestion() {
		var q = QUESTIONS[symbol], box = root.querySelector('.mg-question');
		box.textContent = '';
		box.appendChild(el('h3', '', q.question));
		q.answers.forEach(function (text, i) {
			var btn = el('button', '', text);
			btn.addEventListener('click', function () { answer(i, q, box); });
			box.appendChild(btn);
		});
	}

	function answer(i, q, box) {
		var ok = i === q.correct;
		box.textContent = '';
		box.appendChild(el('p', '', ok ? 'Richtig!' : 'Falsch. Richtig wäre: ' + q.answers[q.correct]));
		var status = el('p', '', 'Ergebnis wird gespeichert …');
		box.appendChild(status);
		submitResult({
			session_id: sessionId,
			round_number: round,
			duration_ms: duration,
			attempts: attempts,
			question_id: 'q' + SYMBOLS.indexOf(symbol),
			question: q.question,
			answer: q.answers[i],
			is_correct: ok
		}).then(function (res) {
			status.textContent = res.ok ? 'Ergebnis gespeichert.' : 'Speichern fehlgeschlagen.';
		}).catch(function () {
			status.textContent = 'Speichern fehlgeschlagen.';
		});
		var next = el('button', '', 'Nächstes Spiel');
		next.addEventListener('click', startRound);
		box.appendChild(next);
	}

	startRound();
})();
