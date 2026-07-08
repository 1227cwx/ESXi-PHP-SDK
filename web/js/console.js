(() => {
  const qs = new URLSearchParams(location.search);
  const vmInput = document.getElementById('vmName');
  const vmLabel = document.getElementById('vmNameLabel');
  const connectBtn = document.getElementById('connectBtn');
  const wakeBtn = document.getElementById('wakeBtn');
  const cadBtn = document.getElementById('cadBtn');
  const disconnectBtn = document.getElementById('disconnectBtn');
  const fullscreenBtn = document.getElementById('fullscreenBtn');
  const statusEl = document.getElementById('status');
  const wsUrlEl = document.getElementById('wsUrl');
  const wsProtocolEl = document.getElementById('wsProtocol');
  const esxiHttpsEl = document.getElementById('esxiHttps');
  const logEl = document.getElementById('log');

  let wmks = null;
  let consoleUsableMarked = false;

  const initialVm = qs.get('vm') || 'test';
  vmInput.value = initialVm;
  vmLabel.textContent = initialVm;

  function log(message, data) {
    const time = new Date().toLocaleTimeString();
    const suffix = data ? ` ${JSON.stringify(data)}` : '';
    logEl.textContent += `[${time}] ${message}${suffix}\n`;
    logEl.scrollTop = logEl.scrollHeight;
    console.log('[ESXi Console Demo]', message, data || '');
  }

  function setStatus(message) {
    statusEl.textContent = message;
    log(message);
  }

  function enableConsoleButtons(enabled) {
    disconnectBtn.disabled = !enabled;
    fullscreenBtn.disabled = !enabled;
    wakeBtn.disabled = !enabled;
    cadBtn.disabled = !enabled;
  }

  function resetConsoleContainer(message) {
    const current = document.getElementById('wmksContainer');
    if (!current) {
      return;
    }

    const fresh = document.createElement('div');
    fresh.id = 'wmksContainer';
    fresh.className = 'wmks-container';

    if (message) {
      const placeholder = document.createElement('div');
      placeholder.className = 'console-placeholder';
      placeholder.textContent = message;
      fresh.appendChild(placeholder);
    }

    current.replaceWith(fresh);
  }

  function destroyWmks(instance) {
    if (!instance) {
      return;
    }

    try {
      instance.disconnect && instance.disconnect();
    } catch (error) {
      log('\u65ad\u5f00 WebMKS \u5931\u8d25', { message: error.message });
    }

    try {
      instance.destroy && instance.destroy();
    } catch (error) {
      log('\u9500\u6bc1 WebMKS \u5b9e\u4f8b\u5931\u8d25', { message: error.message });
    }
  }

  function cleanupDisconnected(message, instance, shouldDestroy) {
    const target = instance || wmks;
    if (instance && wmks !== instance && wmks !== null) {
      return;
    }

    wmks = null;
    consoleUsableMarked = false;

    if (shouldDestroy) {
      destroyWmks(target);
    }

    connectBtn.disabled = false;
    enableConsoleButtons(false);
    wsUrlEl.textContent = '-';
    wsProtocolEl.textContent = '-';
    resetConsoleContainer('\u63a7\u5236\u53f0\u5df2\u65ad\u5f00');
    setStatus(message || '\u5df2\u65ad\u5f00');
  }

  function focusConsole() {
    const canvas = document.querySelector('#wmksContainer canvas');
    if (canvas) {
      canvas.focus();
    }
    try {
      wmks && wmks.grab && wmks.grab();
    } catch (error) {
      log('grab \u5931\u8d25', { message: error.message });
    }
  }

  function markConsoleUsable() {
    if (consoleUsableMarked) {
      return;
    }
    consoleUsableMarked = true;
    setStatus('\u5df2\u8fde\u63a5\uff0c\u7b49\u5f85/\u663e\u793a\u63a7\u5236\u53f0\u753b\u9762');
    connectBtn.disabled = true;
    enableConsoleButtons(true);
    setTimeout(() => wmks && wmks.updateScreen && wmks.updateScreen(), 100);
    setTimeout(focusConsole, 150);
  }

  function ensureWmks() {
    if (wmks) {
      return wmks;
    }
    if (!window.WMKS || typeof WMKS.createWMKS !== 'function') {
      throw new Error('WMKS \u524d\u7aef\u5e93\u672a\u52a0\u8f7d\u3002');
    }

    WMKS.LOGGER && WMKS.LOGGER.setLogLevel && WMKS.LOGGER.setLogLevel(WMKS.LOGGER.LEVEL.INFO);
    const client = WMKS.createWMKS('wmksContainer', {
      // Keep the same core options as the ESXi 6.7 Host Client MKSService.
      rescale: true,
      changeResolution: true,
      useNativePixels: false,
      audioEncodeType: WMKS.CONST && WMKS.CONST.AudioEncodeType ? WMKS.CONST.AudioEncodeType.OPUS : null,
      keyboardLayoutId: 'en-US',
      retryConnectionInterval: -1,
      enableAdvancedTouch: false,
      allowMobileKeyboardInput: false,
      allowMobileExtendedKeypad: false,
      allowMobileTrackpad: false,
      disableTouch: true
    });
    wmks = client;

    if (typeof client.register === 'function' && WMKS.CONST && WMKS.CONST.Events) {
      client.register(WMKS.CONST.Events.CONNECTION_STATE_CHANGE, (_event, data) => {
        if (wmks !== client) {
          return;
        }

        const state = data && data.state ? data.state : client.getConnectionState && client.getConnectionState();
        log('\u8fde\u63a5\u72b6\u6001\u53d8\u5316', { state });
        if (state === WMKS.CONST.ConnectionState.CONNECTING) {
          setStatus('WebMKS \u6b63\u5728\u521d\u59cb\u5316');
          return;
        }
        if (state === WMKS.CONST.ConnectionState.CONNECTED) {
          setStatus('\u5df2\u8fde\u63a5\uff0c\u53ef\u4ee5\u64cd\u4f5c\u63a7\u5236\u53f0');
          markConsoleUsable();
          return;
        }
        if (state === WMKS.CONST.ConnectionState.DISCONNECTED) {
          cleanupDisconnected('\u5df2\u65ad\u5f00', client, false);
        }
      });

      client.register(WMKS.CONST.Events.REMOTE_SCREEN_SIZE_CHANGE, (_event, data) => {
        if (wmks !== client) {
          return;
        }
        log('\u8fdc\u7aef\u684c\u9762\u5c3a\u5bf8\u53d8\u5316', data || {});
        markConsoleUsable();
      });

      client.register(WMKS.CONST.Events.ERROR, (_event, data) => {
        if (wmks !== client) {
          return;
        }
        const message = 'WebMKS \u9519\u8bef\uff1a' + ((data && data.errorType) || 'unknown');
        console.error('WebMKS error', data);
        cleanupDisconnected(message, client, false);
      });
    } else {
      log('\u5f53\u524d WMKS \u5e93\u6ca1\u6709 register \u4e8b\u4ef6\u63a5\u53e3\uff0c\u5c06\u53ea\u80fd\u663e\u793a\u57fa\u7840\u8fde\u63a5\u65e5\u5fd7\u3002');
    }

    return wmks;
  }

  async function fetchTicket(vm) {
    const url = `/api/console-ticket.php?vm=${encodeURIComponent(vm)}&type=webmks&_=${Date.now()}`;
    const response = await fetch(url, { cache: 'no-store' });
    const json = await response.json();
    if (!response.ok || !json.success) {
      throw new Error(json.error?.message || '\u83b7\u53d6\u63a7\u5236\u53f0 Ticket \u5931\u8d25');
    }
    return json.data;
  }

  async function connect() {
    const vm = vmInput.value.trim() || 'test';
    vmLabel.textContent = vm;
    connectBtn.disabled = true;
    consoleUsableMarked = false;
    setStatus('\u6b63\u5728\u83b7\u53d6 WebMKS Ticket');

    try {
      const ticket = await fetchTicket(vm);
      wsUrlEl.textContent = ticket.websocket_url || '-';
      wsProtocolEl.textContent = ticket.websocket_subprotocol || '-';
      if (ticket.host) {
        esxiHttpsEl.textContent = `https://${ticket.host}/`;
      }
      log('Ticket \u83b7\u53d6\u6210\u529f', {
        host: ticket.host,
        port: ticket.port,
        path: ticket.websocket_path,
        subprotocol: ticket.websocket_subprotocol,
        ticketLength: ticket.ticket ? String(ticket.ticket).length : 0
      });

      if (!ticket.websocket_url) {
        throw new Error('\u63a5\u53e3\u6ca1\u6709\u8fd4\u56de websocket_url\u3002');
      }

      resetConsoleContainer();
      const client = ensureWmks();
      setStatus('\u6b63\u5728\u8fde\u63a5 ' + ticket.websocket_url);
      client.connect(ticket.websocket_url);
      enableConsoleButtons(true);
    } catch (error) {
      cleanupDisconnected('\u8fde\u63a5\u5931\u8d25\uff1a' + error.message, wmks, true);
      console.error(error);
    }
  }

  function disconnect() {
    const current = wmks;
    cleanupDisconnected('\u5df2\u65ad\u5f00', current, true);
    log('WebMKS \u5df2\u65ad\u5f00\uff0c\u63a7\u5236\u53f0\u753b\u9762\u5df2\u6e05\u7a7a');
  }

  function wakeConsole() {
    if (!wmks) {
      return;
    }
    focusConsole();
    try {
      wmks.sendKeyCodes && wmks.sendKeyCodes([13], [28]);
      log('\u5df2\u53d1\u9001 Enter \u5524\u9192\u952e');
    } catch (error) {
      log('\u53d1\u9001 Enter \u5931\u8d25', { message: error.message });
    }
  }

  function sendCtrlAltDel() {
    if (!wmks) {
      return;
    }
    focusConsole();
    try {
      wmks.sendCAD && wmks.sendCAD();
      log('\u5df2\u53d1\u9001 Ctrl+Alt+Del');
    } catch (error) {
      log('\u53d1\u9001 Ctrl+Alt+Del \u5931\u8d25', { message: error.message });
    }
  }

  function fullscreen() {
    if (wmks && wmks.canFullScreen && wmks.canFullScreen()) {
      wmks.enterFullScreen && wmks.enterFullScreen();
      return;
    }
    const shell = document.getElementById('consoleShell');
    if (shell.requestFullscreen) {
      shell.requestFullscreen();
    }
  }

  connectBtn.addEventListener('click', connect);
  wakeBtn.addEventListener('click', wakeConsole);
  cadBtn.addEventListener('click', sendCtrlAltDel);
  disconnectBtn.addEventListener('click', disconnect);
  fullscreenBtn.addEventListener('click', fullscreen);
  document.getElementById('consoleShell').addEventListener('click', focusConsole);

  window.__esxiConsoleDebug = {
    get wmks() {
      return wmks;
    },
    focusConsole,
    wakeConsole,
    sendCtrlAltDel,
    disconnect,
    state: () => ({
      wmksVersion: window.WMKS && WMKS.version,
      wmksBuild: window.WMKS && WMKS.buildNumber,
      connectionState: wmks && wmks.getConnectionState && wmks.getConnectionState(),
      remoteSize: wmks && wmks.getRemoteScreenSize && wmks.getRemoteScreenSize(),
      canvas: Array.from(document.querySelectorAll('#wmksContainer canvas')).map((canvas) => ({
        width: canvas.width,
        height: canvas.height,
        clientWidth: canvas.clientWidth,
        clientHeight: canvas.clientHeight
      })),
      placeholder: document.querySelector('#wmksContainer .console-placeholder')?.textContent || null
    })
  };

  resetConsoleContainer('\u63a7\u5236\u53f0\u672a\u8fde\u63a5');
  log('Demo \u5df2\u52a0\u8f7d\u3002\u70b9\u51fb\u201c\u8fde\u63a5\u63a7\u5236\u53f0\u201d\u5f00\u59cb\u6d4b\u8bd5\u3002');
  if (qs.get('autoconnect') === '1') {
    setTimeout(connect, 300);
  }
})();
