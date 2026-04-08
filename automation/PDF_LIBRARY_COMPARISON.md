# PDF Library Comparison: TCPDF vs mPDF

This document compares the two PDF generation libraries available for the Interview Bank project.

## Quick Comparison

| Feature | TCPDF | mPDF |
|---------|-------|------|
| **Default Library** | ✅ Yes | No (optional) |
| **Installation Size** | ~10MB | ~15MB |
| **HTML/CSS Support** | Basic | Excellent |
| **File Size** | Smaller (~500KB-1MB) | Larger (~1-2MB) |
| **Performance** | Faster | Slightly slower |
| **Unicode Support** | ✅ Excellent | ✅ Excellent |
| **Customization** | High (programmatic) | High (CSS-based) |
| **Learning Curve** | Steeper | Easier |
| **Maintenance** | Active | Active |

## TCPDF (Default)

### Installation
```bash
composer install
```

### Usage
```bash
php automation/generate-pdf-answers.php
php automation/generate-pdf-answers.php php-core-answers
```

### Pros
- ✅ Already included in dependencies
- ✅ More control over PDF generation
- ✅ Smaller output file sizes
- ✅ Faster processing
- ✅ No external dependencies
- ✅ Better for programmatic content generation
- ✅ Excellent documentation

### Cons
- ❌ More code required for styling
- ❌ Limited HTML/CSS support
- ❌ Steeper learning curve
- ❌ More verbose API

### Best For
- Complex, programmatically generated content
- When file size matters
- When you need fine-grained control
- Performance-critical applications

## mPDF (Optional)

### Installation
```bash
composer require mpdf/mpdf
```

### Usage
```bash
php automation/generate-pdf-answers-mpdf.php
php automation/generate-pdf-answers-mpdf.php php-core-answers
```

### Pros
- ✅ Excellent HTML/CSS support
- ✅ Easier to use (HTML-based)
- ✅ Better rendering of complex layouts
- ✅ Simpler for HTML conversion
- ✅ More intuitive API
- ✅ Better support for web fonts

### Cons
- ❌ Larger file sizes
- ❌ Slightly slower processing
- ❌ Requires additional installation
- ❌ More memory intensive

### Best For
- Converting existing HTML to PDF
- When CSS styling is important
- Simpler projects with HTML templates
- When ease of use is priority

## Feature Support Comparison

### Text and Typography
| Feature | TCPDF | mPDF |
|---------|-------|------|
| Custom fonts | ✅ | ✅ |
| Web fonts | ❌ | ✅ |
| Text alignment | ✅ | ✅ |
| Text wrapping | ✅ | ✅ |
| Kerning | ✅ | Limited |

### Layout
| Feature | TCPDF | mPDF |
|---------|-------|------|
| Multi-column | ✅ | ✅ |
| Headers/Footers | ✅ | ✅ |
| Page breaks | ✅ | ✅ |
| Bookmarks | ✅ | ✅ |
| TOC generation | Manual | ✅ Auto |

### Content
| Feature | TCPDF | mPDF |
|---------|-------|------|
| Images | ✅ | ✅ |
| Tables | ✅ | ✅ |
| Lists | ✅ | ✅ |
| Code blocks | Manual | ✅ CSS |
| SVG | Basic | ✅ |

### Styling
| Feature | TCPDF | mPDF |
|---------|-------|------|
| CSS support | Limited | Excellent |
| Inline styles | Limited | ✅ |
| CSS classes | ❌ | ✅ |
| Gradients | Manual | ✅ CSS |
| Borders | ✅ | ✅ |

## Performance Benchmarks

Based on typical interview answer documents:

### TCPDF
- **Processing time**: 2-3 seconds per document
- **Memory usage**: 50-80MB peak
- **Output size**: 500KB-1MB per document
- **Quality**: Excellent

### mPDF
- **Processing time**: 3-5 seconds per document
- **Memory usage**: 80-120MB peak
- **Output size**: 1-2MB per document
- **Quality**: Excellent

## Code Examples

### TCPDF Approach
```php
// More programmatic, detailed control
$pdf = new PDFAnswerGenerator('P', 'mm', 'A4');
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetTextColor(102, 126, 234);
$pdf->MultiCell(0, 10, $heading, 0, 'L');

// Code blocks require manual styling
$pdf->SetFillColor(40, 44, 52);
$pdf->SetTextColor(171, 178, 191);
$pdf->SetFont('courier', '', 8);
foreach ($codeLines as $line) {
    $pdf->Cell(0, 4, $line, 0, true, 'L', 1);
}
```

### mPDF Approach
```php
// More HTML/CSS-based, simpler
$mpdf = new Mpdf();

// Direct HTML with CSS
$html = '
<style>
h2 { color: #667eea; font-size: 18pt; }
pre { 
    background: #282c34; 
    color: #abb2bf; 
    padding: 10px; 
}
</style>
<h2>My Heading</h2>
<pre><code>function example() {
    return "Hello World";
}</code></pre>
';

$mpdf->WriteHTML($html);
```

## Choosing the Right Library

### Use TCPDF if:
- You need the smallest possible file sizes
- Performance is critical
- You're generating content programmatically
- You need precise control over every element
- You don't have complex HTML/CSS to convert

### Use mPDF if:
- You're converting existing HTML documents
- You want to use CSS for styling
- Ease of development is more important than file size
- You need better HTML/CSS compatibility
- You want simpler code maintenance

## Switching Between Libraries

The project includes both implementations, so you can easily switch:

### For TCPDF (default):
```bash
php automation/generate-pdf-answers.php
./automation/generate-pdfs.sh
```

### For mPDF:
```bash
# Install if not already installed
composer require mpdf/mpdf

# Generate PDFs
php automation/generate-pdf-answers-mpdf.php
```

## Recommendation

For the Interview Bank project:

**Use TCPDF (default)** because:
1. ✅ Better performance for batch generation
2. ✅ Smaller file sizes for offline distribution
3. ✅ Already included in dependencies
4. ✅ Sufficient for our needs (we build content programmatically)
5. ✅ Better for generating many PDFs at once

**Consider mPDF if:**
1. You prefer working with HTML/CSS
2. You want to quickly prototype PDF layouts
3. You need better visual fidelity to HTML source
4. You're adding complex layouts with advanced CSS

## Installation Instructions

### TCPDF (Already Included)
```bash
# Clone the repository
git clone <repository-url>
cd interview-bank

# Install dependencies
composer install

# Generate PDFs
php automation/generate-pdf-answers.php
```

### Adding mPDF (Optional)
```bash
# Install mPDF alongside TCPDF
composer require mpdf/mpdf

# Use mPDF generator
php automation/generate-pdf-answers-mpdf.php
```

## Troubleshooting

### TCPDF Issues
- **Memory errors**: Increase PHP memory limit
- **Missing fonts**: Check TCPDF font installation
- **Rendering issues**: Verify content structure

### mPDF Issues
- **CSS not applied**: Check CSS syntax and selector specificity
- **Large file sizes**: Optimize images and reduce redundant CSS
- **Memory errors**: Increase memory_limit in php.ini

## Resources

### TCPDF
- **Website**: https://tcpdf.org/
- **Documentation**: https://tcpdf.org/docs/
- **GitHub**: https://github.com/tecnickcom/TCPDF
- **Examples**: https://tcpdf.org/examples/

### mPDF
- **Website**: https://mpdf.github.io/
- **Documentation**: https://mpdf.github.io/reference/mpdf-functions/overview.html
- **GitHub**: https://github.com/mpdf/mpdf
- **CSS Support**: https://mpdf.github.io/css-stylesheets/supported-css.html

## Conclusion

Both libraries are excellent choices for PDF generation. TCPDF is the default recommendation for this project due to better performance and smaller output sizes, but mPDF is available as an alternative if you prefer HTML/CSS-based approach.
