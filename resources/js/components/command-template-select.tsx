import { FormField } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CommandTemplate } from '@/lib/command-templates';

export default function CommandTemplateSelect({
  templates,
  onSelect,
}: {
  templates: CommandTemplate[];
  onSelect: (template: CommandTemplate) => void;
}) {
  if (templates.length === 0) {
    return null;
  }

  return (
    <FormField>
      <Label htmlFor="template">Template</Label>
      <Select
        onValueChange={(value) => {
          const template = templates.find((item) => item.id === value);
          if (template) {
            onSelect(template);
          }
        }}
      >
        <SelectTrigger id="template">
          <SelectValue placeholder="Start from a template (optional)" />
        </SelectTrigger>
        <SelectContent>
          <SelectGroup>
            {templates.map((template) => (
              <SelectItem key={template.id} value={template.id}>
                {template.label}
              </SelectItem>
            ))}
          </SelectGroup>
        </SelectContent>
      </Select>
    </FormField>
  );
}
