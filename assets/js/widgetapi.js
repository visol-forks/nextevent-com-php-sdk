/**
 * NextEventWidgetAPI
 */

(function (root)
  {
  var NextEventWidgetAPI = {};

  /**
   * Collection of all message handlers.
   * @type {Object}
   */
  var message_handlers = {};

  var iframe;

  // list of deprecated post message types (old => new)
  var message_aliases = 
    {
    'current_step':    'currentStep',
    'basket_update':   'basketUpdate',
    'close_widget':    'closeWidget',
    'exit_fullscreen': 'exitFullscreen',
    'scroll_top':      'scrollTop',
    };

  /**
   * Compare post message origin with the location of our embedded iframe
   *
   * @param {Event} e PostMessage event
   * @return {Boolean} True of origin matches, false otherwise
   */
  function check_origin(e)
    {
    if (!iframe) return false;

    var parser = document.createElement('a');
    parser.href = iframe.src;

    var origin = document.createElement('a');
    origin.href = e.origin;

    return (origin.host == parser.host);
    }

  /**
   * dispatch event according type
   * @param {string} type
   * @param {object} data
   */
  function handle_type(type, data)
    {
    if (Array.isArray(message_handlers[type]))
      message_handlers[type].forEach(function (handler)
      {
      handler(data);
      });
    }

  /**
   * Initialize NextEventWidgetAPI and register Message Listener
   */
  function initialize()
    {
    // search for iframe
    var frames = document.querySelectorAll('iframe.nextevent');
    if (frames[0])
      iframe = frames[0];

    // listen for messages
    if (window.addEventListener)
      window.addEventListener('message', function (e)
      {
      var data;
      try
        {
        data = JSON.parse(e.data);
        }
      catch (err)
        {
        // ingore error, message is probably not from a widget
        }

      // Do an origin check before processing the message
      if (!check_origin(e) || !data || !data.nextevent)
        return;

      var type = data.type;
      // if there are multiple operations, excute them in a sequence
      if (Array.isArray(type))
        {
        type.forEach(function (t)
        {
        handle_type(t, data)
        });
        }
      // dispatch message according to its type property
      else if (typeof type === 'string')
        {
        handle_type(type, data)
        }
      else
        console.log('unknown post message type: ' + data.type);
      });
    };

  /**
   * Add Message Handler of Type
   * @param {string} type message type identifier
   * @param {function} handler handler function of message type
   */
  NextEventWidgetAPI.addMessageHandler = function (type, handler)
    {
    // rename deprecated type identifier
    if (message_aliases[type])
      type = message_aliases[type];

    var type_handlers = message_handlers[type] || [];
    if (type_handlers.indexOf(handler) === -1)
      type_handlers.push(handler);
    message_handlers[type] = type_handlers;
    };

  /**
   * Remove Message Handler of Type
   * @param {string} type message type identifier
   * @param {function} handler handler function to be removed
   */
  NextEventWidgetAPI.removeMessageHandler = function (type, handler)
    {
    // rename deprecated type identifier
    if (message_aliases[type])
      type = message_aliases[type];

    if (message_handlers[type])
      {
      var idx = message_handlers[type].indexOf(handler);
      if (idx >= 0)
        message_handlers[type] = message_handlers[type].splice(idx, 1);
      }
    };

  initialize();
  root.NextEventWidgetAPI = NextEventWidgetAPI;
  return NextEventWidgetAPI;
  }(this));
