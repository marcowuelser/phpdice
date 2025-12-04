#!/bin/bash
for file in *.php; do
    # Create backup
    cp "$file" "$file.bak"
    
    # Fix pattern: $var = $phpdice->parse('...'); followed by $result = $phpdice->roll($var);
    # Replace with: $result = $phpdice->roll('...');
    
    perl -i -pe '
        # Store the expression from parse line
        if (/\$(\w+)\s*=\s*\$phpdice->parse\((.*?)\);/) {
            $var = $1;
            $expr = $2;
            $_ = "";  # Remove this line
            $next_line = <>;
            # Check if next line uses this variable in roll
            if ($next_line =~ /\$(\w+)\s*=\s*\$phpdice->roll\(\$$var\);/) {
                $result_var = $1;
                $_ = "\$$result_var = \$phpdice->roll($expr);\n";
            } else {
                # Put both lines back if pattern doesnt match
                $_ = "\$$var = \$phpdice->parse($expr);\n$next_line";
            }
        }
    ' "$file"
    
    echo "Fixed $file"
done
