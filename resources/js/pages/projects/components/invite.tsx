import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Project } from '@/types/project';
import { useForm, usePage } from '@inertiajs/react';
import { SharedData } from '@/types';
import { Checkbox } from '@/components/ui/checkbox';
import { PasswordInput } from '@/components/ui/password-input';
import { LoaderCircleIcon } from 'lucide-react';
import { FormEvent, ReactNode, useState } from 'react';
import RolePermissions from '@/pages/projects/components/role-permissions';

export default function Invite({ project, onInviteSent, children }: { project: Project; onInviteSent?: () => void; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const [createAccount, setCreateAccount] = useState(false);
  const canCreateAccount = usePage<SharedData>().props.auth.user?.is_admin ?? false;
  const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'user',
  });

  const changeOpen = (value: boolean) => {
    setOpen(value);
    if (!value) {
      setCreateAccount(false);
      form.reset();
      form.clearErrors();
    }
  };

  const toggleCreateAccount = (value: boolean) => {
    setCreateAccount(value);
    form.setData({ ...form.data, name: '', password: '' });
    form.clearErrors();
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const url = createAccount ? `/settings/projects/${project.id}/users/create` : `/settings/projects/${project.id}/users`;
    form.post(url, {
      onSuccess: () => {
        changeOpen(false);
        if (onInviteSent) {
          onInviteSent();
        }
      },
    });
  };
  return (
    <Dialog open={open} onOpenChange={changeOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Invite users to project</DialogTitle>
          <DialogDescription className="sr-only">Invite a new user to project</DialogDescription>
        </DialogHeader>
        <Form id="invite-form" onSubmit={submit} className="p-4">
          <FormFields>
            {canCreateAccount && (
              <FormField>
                <div className="flex items-center gap-2">
                  <Checkbox id="create-account" checked={createAccount} onCheckedChange={(checked) => toggleCreateAccount(checked === true)} />
                  <Label htmlFor="create-account">Create an account instead of sending an invitation</Label>
                </div>
              </FormField>
            )}
            {createAccount && (
              <FormField>
                <Label htmlFor="name">Name</Label>
                <Input id="name" name="name" autoComplete="off" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                <InputError message={form.errors.name} />
              </FormField>
            )}
            <FormField>
              <Label htmlFor="email">Email</Label>
              <Input id="email" name="email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
              <InputError message={form.errors.email} />
            </FormField>
            {createAccount && (
              <FormField>
                <Label htmlFor="password">Password</Label>
                <PasswordInput
                  id="password"
                  name="password"
                  value={form.data.password}
                  onChange={(e) => form.setData('password', e.target.value)}
                  autoComplete="new-password"
                />
                <p className="text-muted-foreground text-xs">
                  At least 8 characters. Share it with the person; they can change it from their profile.
                </p>
                <InputError message={form.errors.password} />
              </FormField>
            )}
            <FormField>
              <Label htmlFor="role">Role</Label>
              <Select defaultValue={form.data.role} onValueChange={(value) => form.setData('role', value)}>
                <SelectTrigger id="role" name="role" className="w-full">
                  <SelectValue placeholder="Select a role" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="admin">Admin</SelectItem>
                    <SelectItem value="user">User</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.role} />
            </FormField>
            <RolePermissions />
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          <Button form="invite-form" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            {createAccount ? 'Create account' : 'Invite'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
