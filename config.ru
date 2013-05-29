require 'rack'
require 'rack-legacy'
require 'rack-rewrite'

INDEXES = ['index.html','index.php', 'index.cgi']

ENV['SERVER_PROTOCOL'] = "HTTP/1.1"

use Rack::Rewrite do
  rewrite %r{(.*/$)}, lambda {|match, rack_env|
    INDEXES.each do |index|
      if File.exists?(File.join(Dir.getwd, rack_env['PATH_INFO'], index))
        return rack_env['PATH_INFO'] + index
      end
    end
    rack_env['PATH_INFO']
  }
end

use Rack::Legacy::Php, Dir.getwd, '/usr/local/bin/php-cgi'
use Rack::Legacy::Cgi, Dir.getwd
run Rack::File.new Dir.getwd