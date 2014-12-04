/*
---
description: Simulating process threading in a javascript loop, using MooTools.
license: MIT-style
authors: [Christopher Pitt]
provides: [ThreadedLoop]
requires: 
  core/1.2.4: [Class.Extras, Number, Array]
...
*/

(function(context) {

	var ThreadedLoop = new Class({
		'Implements': [Events, Options],
		'options': {
			/*
			'onInitialize': $empty,
			'onStart': $empty,
			'onStop': $empty,
			'onPause': $empty,
			'onResume': $empty,
			'onProcess': $empty,
			'onComplete': $empty,
			*/
			'chunk': 10,
			'interval': 1
		},
		'initialize': function(source, options)
		{
			this.setOptions(options);

			if (typeOf(source) == 'number')
			{
				this.type = 'number';
				this.limit = source;
			}
			else if (typeOf(source) == 'array')
			{
				this.type = 'array';
				this.array = source;
				this.limit = source.length;
			}
			else
			{
				throw('Source is not of type Number or Array!');
			}

			this.index = 0;

			self.fireEvent('onInitialize', [self.index]);
	    },

		'stop': function()
		{
			clearInterval(this.interval);       
			this.index = 0;    
			this.fireEvent('onStop', [this.index]);    
		},

		'start': function(index)
		{
			this.index = (index || 0);
			this.interval = setInterval(this.process.bind(this), this.options['interval']);
			this.fireEvent('onStart', [index]);
		},

		'pause': function()
		{
			clearInterval(this.interval);
			this.fireEvent('onPause', [this.index]);
		},

		'resume': function()
		{
			this.start(this.index);
			this.fireEvent('onResume', [this.index]);
		},

		'process': function()
		{
			var self = this,
				array = self.array,
				type = self.type,
				limit = self.limit,
				chunk = self.index + self.options['chunk'];

			if (type == 'number')
			{
				while ((self.index < chunk) && (self.index < limit))
				{
					self.fireEvent('onProcess', [self.index]);            
					self.index++;
				}
			}
			else
			{
				while ((self.index < chunk) && (self.index < limit))
				{
					self.fireEvent('onProcess', [array[self.index], self.index]);          
					self.index++;
				}
			}

			if (self.index == self.limit)
			{
				self.stop();
				self.fireEvent('onComplete', [self.limit]);
			}
		}
	});

	Array.implement({
		'loop': function(options)
		{
			new ThreadedLoop(this, options).start();
			return this;
		}
	});

	context.ThreadedLoop = ThreadedLoop;

})(window ? window : this);