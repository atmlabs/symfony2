<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\SwiftmailerBundle\Command;

use Symfony2\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony2\Component\Console\Input\InputInterface;
use Symfony2\Component\Console\Input\InputOption;
use Symfony2\Component\Console\Output\OutputInterface;
use Symfony2\Component\Console\Helper\DialogHelper;
use Symfony2\Component\Console\Helper\QuestionHelper;
use Symfony2\Component\Console\Question\Question;

/**
 * A console command for creating and sending simple emails
 *
 * @author Gusakov Nikita <dev@nkt.me>
 */
class NewEmailCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('swiftmailer:email:send')
            ->setDescription('Send simple email message')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'The from address of the message')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'The to address of the message')
            ->addOption('subject', null, InputOption::VALUE_REQUIRED, 'The subject of the message')
            ->addOption('body', null, InputOption::VALUE_REQUIRED, 'The body of the message')
            ->addOption('mailer', null, InputOption::VALUE_REQUIRED, 'The mailer name', 'default')
            ->addOption('content-type', null, InputOption::VALUE_REQUIRED, 'The body content type of the message', 'text/html')
            ->addOption('charset', null, InputOption::VALUE_REQUIRED, 'The body charset of the message', 'UTF8')
            ->addOption('body-source', null, InputOption::VALUE_REQUIRED, 'The source where body come from [stdin|file]', 'stdin')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command creates and send simple email message.

<info>php %command.full_name% --mailer=custom_mailer --content-type=text/xml</info>

You can get body of message from file:
<info>php %command.full_name% --body-source=file --body=/path/to/file</info>

EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailerServiceName = sprintf('swiftmailer.mailer.%s', $input->getOption('mailer'));
        if (!$this->getContainer()->has($mailerServiceName)) {
            throw new \InvalidArgumentException(sprintf('The mailer "%s" does not exist', $input->getOption('mailer')));
        }
        switch ($input->getOption('body-source')) {
            case 'file':
                $filename = $input->getOption('body');
                $content = file_get_contents($filename);
                if ($content === false) {
                    throw new \Exception('Could not get contents from ' . $filename);
                }
                $input->setOption('body', $content);
                break;
            case 'stdin':
                break;
            default:
                throw new \InvalidArgumentException('Body-input option should be "stdin" or "file"');
        }

        $message = $this->createMessage($input);
        $mailer = $this->getContainer()->get($mailerServiceName);
        $output->writeln(sprintf('<info>Sent %s emails<info>', $mailer->send($message)));
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // Symfony <2.5 BC
        /** @var QuestionHelper|DialogHelper $questionHelper */
        $questionHelper = $this->getHelperSet()->has('question') ? $this->getHelperSet()->get('question') : $this->getHelperSet()->get('dialog');

        foreach ($input->getOptions() as $option => $value) {
            if ($value === null) {
                // Symfony <2.5 BC
                if ($questionHelper instanceof QuestionHelper) {
                    $question = new Question(sprintf('<question>%s</question>: ', ucfirst($option)));
                } else {
                    $question = sprintf('<question>%s</question>: ', ucfirst($option));
                }
                $input->setOption($option, $questionHelper->ask($input, $output, $question));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->getContainer()->has('mailer');
    }

    /**
     * Creates new message from input options.
     *
     * @param InputInterface $input An InputInterface instance
     *
     * @return \Swift_Message New message
     */
    private function createMessage(InputInterface $input)
    {
        $message = \Swift_Message::newInstance(
            $input->getOption('subject'),
            $input->getOption('body'),
            $input->getOption('content-type'),
            $input->getOption('charset')
        );
        $message->setFrom($input->getOption('from'));
        $message->setTo($input->getOption('to'));

        return $message;
    }
}
