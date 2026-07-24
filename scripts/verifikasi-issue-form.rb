#!/usr/bin/env ruby

require 'yaml'
require 'uri'

ROOT = File.expand_path('..', __dir__)
FORM_PATHS = [
  '.github/ISSUE_TEMPLATE/laporan-bug-insiden.yml',
  '.github/ISSUE_TEMPLATE/permintaan-perubahan.yml'
].freeze
ALLOWED_TYPES = %w[markdown input textarea dropdown checkboxes].freeze

errors = []

def load_yaml(relative_path, errors)
  full_path = File.join(ROOT, relative_path)
  data = YAML.safe_load(File.read(full_path), permitted_classes: [], aliases: false)
  unless data.is_a?(Hash)
    errors << "#{relative_path}: root YAML harus berupa object."
    return {}
  end
  data
rescue StandardError => e
  errors << "#{relative_path}: gagal dibaca: #{e.message}"
  {}
end

config = load_yaml('.github/ISSUE_TEMPLATE/config.yml', errors)
errors << 'config.yml: blank_issues_enabled wajib false.' unless config['blank_issues_enabled'] == false

contact_links = config['contact_links']
if !contact_links.is_a?(Array) || contact_links.empty?
  errors << 'config.yml: minimal satu contact_links wajib tersedia untuk laporan privat.'
else
  contact_links.each_with_index do |link, index|
    unless link.is_a?(Hash)
      errors << "config.yml: contact_links[#{index}] harus berupa object."
      next
    end

    %w[name url about].each do |key|
      value = link[key]
      errors << "config.yml: contact_links[#{index}].#{key} wajib diisi." unless value.is_a?(String) && !value.strip.empty?
    end

    begin
      uri = URI.parse(link['url'].to_s)
      errors << "config.yml: contact_links[#{index}].url wajib HTTPS." unless uri.is_a?(URI::HTTPS) && uri.host
    rescue URI::InvalidURIError
      errors << "config.yml: contact_links[#{index}].url tidak valid."
    end
  end
end

FORM_PATHS.each do |relative_path|
  form = load_yaml(relative_path, errors)
  next if form.empty?

  %w[name description].each do |key|
    value = form[key]
    errors << "#{relative_path}: #{key} wajib diisi." unless value.is_a?(String) && !value.strip.empty?
  end

  title = form['title']
  errors << "#{relative_path}: title wajib berupa string." unless title.is_a?(String)

  body = form['body']
  unless body.is_a?(Array) && !body.empty?
    errors << "#{relative_path}: body wajib berupa array yang tidak kosong."
    next
  end

  ids = []
  confirmation_found = false

  body.each_with_index do |item, index|
    unless item.is_a?(Hash)
      errors << "#{relative_path}: body[#{index}] harus berupa object."
      next
    end

    type = item['type'].to_s
    errors << "#{relative_path}: body[#{index}].type tidak didukung: #{type}." unless ALLOWED_TYPES.include?(type)

    attributes = item['attributes']
    unless attributes.is_a?(Hash)
      errors << "#{relative_path}: body[#{index}].attributes wajib berupa object."
      next
    end

    if type == 'markdown'
      value = attributes['value']
      errors << "#{relative_path}: markdown body[#{index}] wajib memiliki value." unless value.is_a?(String) && !value.strip.empty?
      next
    end

    id = item['id'].to_s
    if id.empty?
      errors << "#{relative_path}: body[#{index}] wajib memiliki id."
    elsif ids.include?(id)
      errors << "#{relative_path}: id '#{id}' digunakan lebih dari satu kali."
    else
      ids << id
    end

    label = attributes['label']
    errors << "#{relative_path}: body[#{index}] wajib memiliki label." unless label.is_a?(String) && !label.strip.empty?

    validations = item['validations']
    if validations && !validations.is_a?(Hash)
      errors << "#{relative_path}: body[#{index}].validations harus berupa object."
    elsif validations&.key?('required') && ![true, false].include?(validations['required'])
      errors << "#{relative_path}: body[#{index}].validations.required harus boolean."
    end

    next unless %w[dropdown checkboxes].include?(type)

    options = attributes['options']
    unless options.is_a?(Array) && !options.empty?
      errors << "#{relative_path}: body[#{index}] wajib memiliki options yang tidak kosong."
      next
    end

    options.each_with_index do |option, option_index|
      if type == 'dropdown'
        errors << "#{relative_path}: dropdown body[#{index}] option[#{option_index}] wajib berupa string." unless option.is_a?(String) && !option.strip.empty?
        next
      end

      unless option.is_a?(Hash) && option['label'].is_a?(String) && !option['label'].strip.empty?
        errors << "#{relative_path}: checkboxes body[#{index}] option[#{option_index}] wajib memiliki label."
        next
      end

      if option.key?('required') && ![true, false].include?(option['required'])
        errors << "#{relative_path}: checkboxes body[#{index}] option[#{option_index}].required harus boolean."
      end
    end

    if type == 'checkboxes' && id.match?(/keamanan|konfirmasi/)
      required_options = options.select { |option| option.is_a?(Hash) && option['required'] == true }
      confirmation_found = true unless required_options.empty?
    end
  end

  errors << "#{relative_path}: wajib memiliki checkbox konfirmasi/keamanan yang required." unless confirmation_found
end

if errors.empty?
  puts 'Issue Form dan konfigurasi dukungan valid secara semantik.'
  exit 0
end

warn "Validasi Issue Form gagal:\n- #{errors.join("\n- ")}"
exit 1
