mergeInto(LibraryManager.library, {
  RoxSetDesiredOrientation: function(modePtr) {
    try {
      var mode = UTF8ToString(modePtr || 0);
      var normalizedMode = mode === "landscape" ? "landscape" : "portrait";
      if (document.body && document.body.classList) {
        document.body.classList.remove("webgl-portrait", "webgl-landscape");
        document.body.classList.add("webgl-" + normalizedMode);
      }

      if (typeof screen !== "undefined" && screen.orientation && typeof screen.orientation.lock === "function") {
        var lockTarget = normalizedMode === "landscape" ? "landscape" : "portrait";
        var maybePromise = screen.orientation.lock(lockTarget);
        if (maybePromise && typeof maybePromise.catch === "function") {
          maybePromise.catch(function() {});
        }
      }
    } catch (e) {
      console.warn("RoxSetDesiredOrientation failed", e);
    }
  },

  RoxCopyTextToClipboard: function(textPtr) {
    try {
      var value = UTF8ToString(textPtr || 0);
      function fallbackCopyText(copyValue) {
        if (!document.body) {
          return;
        }
        var textArea = document.createElement("textarea");
        textArea.value = copyValue;
        textArea.setAttribute("readonly", "");
        textArea.style.position = "fixed";
        textArea.style.opacity = "0";
        textArea.style.pointerEvents = "none";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
          document.execCommand("copy");
        } catch (e) {
          console.warn("Clipboard fallback failed", e);
        }
        document.body.removeChild(textArea);
      }

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(value).catch(function() { fallbackCopyText(value); });
        return;
      }
      fallbackCopyText(value);
    } catch (e) {
      console.warn("RoxCopyTextToClipboard failed", e);
    }
  },

  RoxPrepareWebGlTextInput: function(modePtr) {
    try {
      var mode = UTF8ToString(modePtr || 0);
      var normalized = mode === "numeric" ? "numeric" : "default";

      var exitFullscreen =
        document.exitFullscreen ||
        document.webkitExitFullscreen ||
        document.webkitCancelFullScreen ||
        document.mozCancelFullScreen ||
        document.msExitFullscreen;

      if (document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement) {
        try {
          exitFullscreen && exitFullscreen.call(document);
        } catch (e) {}
      }

      var targets = Array.prototype.slice.call(document.querySelectorAll("input, textarea"));
      var isNumeric = normalized === "numeric";
      targets.forEach(function(element) {
        try {
          if (isNumeric) {
            element.setAttribute("inputmode", "numeric");
            element.setAttribute("pattern", "[0-9]*");
            if (element.tagName === "INPUT") {
              element.setAttribute("type", "tel");
            }
          } else {
            element.removeAttribute("inputmode");
            element.removeAttribute("pattern");
            if (element.tagName === "INPUT" && element.getAttribute("type") === "tel") {
              element.setAttribute("type", "text");
            }
          }
        } catch (e) {}
      });
    } catch (e) {
      console.warn("RoxPrepareWebGlTextInput failed", e);
    }
  },

  RoxRequestWebGLMicrophonePermission: function() {
    try {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        return;
      }

      navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
        try {
          stream.getTracks().forEach(function(track) { track.stop(); });
        } catch (e) {}
      }).catch(function(error) {
        console.warn("WebGL microphone permission request failed", error);
      });
    } catch (e) {
      console.warn("RoxRequestWebGLMicrophonePermission failed", e);
    }
  },

  RoxOpenGalleryWebGL: function(targetPtr) {
    try {
      var target = UTF8ToString(targetPtr || 0);
      if (typeof window.openGalleryWebGL === "function") {
        window.openGalleryWebGL(target);
        return;
      }
      console.warn("openGalleryWebGL bridge is not available");
    } catch (e) {
      console.warn("RoxOpenGalleryWebGL failed", e);
    }
  }
});
